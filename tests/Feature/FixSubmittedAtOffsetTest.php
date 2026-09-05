<?php

namespace Tests\Feature;

use App\Models\ObChecklist;
use App\Models\SecurityPatrol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The repair for reports stored seven hours early.
 *
 * This command edits historical evidence, so the thing worth proving is not
 * that it can shift a row — it is that it leaves alone everything it should.
 */
class FixSubmittedAtOffsetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
    }

    /**
     * The real pair from production: pressed Kirim at 23:46, received 23:48,
     * stored as 16:46.
     */
    public function test_it_shifts_a_report_that_carries_the_offset(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-05 16:46:00',
            'created_at' => '2026-09-05 23:48:00',
        ]);

        $this->artisan('nadi:fix-submitted-at-offset', ['--terapkan' => true])
            ->assertSuccessful();

        $this->assertSame(
            '2026-09-05 23:46',
            $checklist->fresh()->submitted_at->format('Y-m-d H:i'),
        );
    }

    /**
     * The safety that matters most: without the flag it reports and stops.
     */
    public function test_it_changes_nothing_without_the_flag(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-05 16:46:00',
            'created_at' => '2026-09-05 23:48:00',
        ]);

        $this->artisan('nadi:fix-submitted-at-offset')
            ->expectsOutputToContain('Belum ada yang diubah')
            ->assertSuccessful();

        $this->assertSame(
            '2026-09-05 16:46',
            $checklist->fresh()->submitted_at->format('Y-m-d H:i'),
        );
    }

    /**
     * An ordinary offline report — filed 20 minutes before it arrived — never
     * carried the offset and must not be touched.
     */
    public function test_it_leaves_an_ordinary_outbox_delay_alone(): void
    {
        $patrol = SecurityPatrol::factory()->create([
            'submitted_at' => '2026-09-05 23:28:00',
            'created_at' => '2026-09-05 23:48:00',
        ]);

        $this->artisan('nadi:fix-submitted-at-offset', ['--terapkan' => true])
            ->assertSuccessful();

        $this->assertSame(
            '2026-09-05 23:28',
            $patrol->fresh()->submitted_at->format('Y-m-d H:i'),
        );
    }

    /**
     * Reports from before the app sent zone-tagged times are outside the
     * window and must stay untouched, however wide their gap looks.
     */
    public function test_it_ignores_reports_outside_the_window(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => '2026-08-20 03:15:00',
            'created_at' => '2026-08-20 11:00:00',
        ]);

        $this->artisan('nadi:fix-submitted-at-offset', ['--terapkan' => true])
            ->assertSuccessful();

        $this->assertSame(
            '2026-08-20 03:15',
            $checklist->fresh()->submitted_at->format('Y-m-d H:i'),
        );
    }

    /**
     * A report with no claimed time has nothing to shift, and must not be
     * given one.
     */
    public function test_it_leaves_a_report_without_a_filed_time_alone(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => null,
            'created_at' => '2026-09-05 23:48:00',
        ]);

        $this->artisan('nadi:fix-submitted-at-offset', ['--terapkan' => true])
            ->assertSuccessful();

        $this->assertNull($checklist->fresh()->submitted_at);
    }

    /**
     * Running it twice must not shift the same row fourteen hours. After the
     * first pass the gap is small, so the row no longer qualifies.
     */
    public function test_it_is_safe_to_run_twice(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-05 16:46:00',
            'created_at' => '2026-09-05 23:48:00',
        ]);

        $this->artisan('nadi:fix-submitted-at-offset', ['--terapkan' => true])->assertSuccessful();
        $this->artisan('nadi:fix-submitted-at-offset', ['--terapkan' => true])->assertSuccessful();

        $this->assertSame(
            '2026-09-05 23:46',
            $checklist->fresh()->submitted_at->format('Y-m-d H:i'),
        );
    }
}
