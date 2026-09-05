<?php

namespace Tests\Feature\Api;

use App\Models\ObArea;
use App\Models\ObChecklist;
use App\Support\FieldReportTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * submitted_at is the one column a phone gets to write a time into, and the
 * whole offline design leans on it being the time the worker actually pressed
 * Kirim. These tests exist because it silently was not.
 *
 * The column stores wall-clock time with no zone. Carbon::parse() keeps
 * whatever zone the string carried, and Eloquent writes a Carbon out in its
 * own zone — so a Z-suffixed time landed seven hours early, and a 03:15 dawn
 * patrol read back as 20:15 the evening before. Nothing failed; the number
 * was just wrong, which is the kind of bug that survives for months.
 */
class FieldReportTimeTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ['ViewAny:ObChecklist', 'Create:ObChecklist'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
        Storage::fake('public');

        // Pinned rather than inherited: under a UTC test run every offset is
        // the same offset, and the bug this file guards cannot happen.
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function equivalentInstants(): array
    {
        return [
            'UTC, as the app sends since 1.0.3' => ['2026-09-05T16:58:00Z'],
            'explicit +07:00' => ['2026-09-05T23:58:00+07:00'],
            'no offset at all, as the app sent before 1.0.3' => ['2026-09-05T23:58:00'],
            'a different zone entirely' => ['2026-09-05T18:58:00+02:00'],
        ];
    }

    /**
     * Every one of these is the same instant: 23:58 in Jakarta. All four have
     * to reach the column as 23:58, because that is the number a supervisor
     * reads.
     */
    #[DataProvider('equivalentInstants')]
    public function test_a_submission_time_is_stored_as_jakarta_wall_clock(string $sent): void
    {
        $this->travelTo('2026-09-06 00:21:18');

        $clamped = FieldReportTime::clamp($sent);

        $this->assertNotNull($clamped);
        $this->assertSame('2026-09-05 23:58:00', $clamped->format('Y-m-d H:i:s'));

        // Through the cast, which is where the zone was actually being lost.
        $checklist = new ObChecklist(['submitted_at' => $clamped]);

        $this->assertSame('2026-09-05 23:58:00', $checklist->getAttributes()['submitted_at']);
    }

    /**
     * The end-to-end version of the same thing: a report written at 23:58 in
     * airplane mode and flushed at 00:21 when signal returned.
     */
    public function test_a_report_flushed_from_the_outbox_keeps_the_time_it_was_written(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);
        $this->travelTo('2026-09-06 00:21:18');

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create(['is_active' => true])->id,
            'photo_ids' => [$this->upload()],
            'submitted_at' => '2026-09-05T16:58:00Z',
        ], $this->idempotencyHeader())->assertCreated();

        $checklist = ObChecklist::query()->sole();

        $this->assertSame('2026-09-05 23:58', $checklist->submitted_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-06 00:21', $checklist->created_at->format('Y-m-d H:i'));

        // The gap is the whole point of keeping both columns.
        $this->assertSame(23, (int) $checklist->submitted_at->diffInMinutes($checklist->created_at));
    }

    /**
     * The clamp still has to work on a zone-tagged value. A phone whose clock
     * is a year fast must not be able to file a report dated next September,
     * whatever offset it writes.
     */
    public function test_a_future_time_is_still_clamped_when_it_carries_a_zone(): void
    {
        $this->travelTo('2026-09-06 00:21:18');

        $clamped = FieldReportTime::clamp('2027-09-05T16:58:00Z');

        $this->assertNotNull($clamped);
        $this->assertSame('2026-09-06 00:21:18', $clamped->format('Y-m-d H:i:s'));
    }

    private function upload(): string
    {
        return $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');
    }
}
