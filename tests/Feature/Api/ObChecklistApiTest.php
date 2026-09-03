<?php

namespace Tests\Feature\Api;

use App\Models\ObArea;
use App\Models\ObChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The reference module, end to end. Whatever holds here is the shape the
 * other three field modules copy.
 */
class ObChecklistApiTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ['ViewAny:ObChecklist', 'Create:ObChecklist'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
        Storage::fake('public');
    }

    public function test_a_worker_can_file_a_report(): void
    {
        $worker = $this->actingAsMobileUser(self::PERMISSIONS);
        $area = ObArea::factory()->create(['is_active' => true]);

        $response = $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => $area->id,
            'notes' => 'Lantai basah, sudah dipel ulang.',
            'photo_ids' => [$this->upload(), $this->upload()],
        ], $this->idempotencyHeader());

        $response->assertCreated();

        $checklist = ObChecklist::query()->sole();

        $this->assertSame($area->id, $checklist->ob_area_id);
        $this->assertSame('Lantai basah, sudah dipel ulang.', $checklist->notes);
        $this->assertSame(2, $checklist->getMedia('photos')->count());

        // Never from the payload — the submitting account owns the report.
        $this->assertSame($worker->id, $checklist->user_id);
    }

    /**
     * NADI.MD §5.7: evidence photos never sit on the public disk, where they
     * are published at a guessable URL with no login. The API is a second
     * doorway into the same collections and must not undo that.
     */
    public function test_photos_land_on_the_private_disk(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $media = ObChecklist::query()->sole()->getMedia('photos')->sole();

        $this->assertSame('internal', $media->disk);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_the_list_only_shows_the_workers_own_reports(): void
    {
        $worker = $this->actingAsMobileUser(self::PERMISSIONS);

        $mine = ObChecklist::factory()->create(['user_id' => $worker->id]);
        $theirs = ObChecklist::factory()->create();

        $response = $this->getJson('/api/v1/ob/checklists');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }

    public function test_another_workers_report_cannot_be_opened(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $theirs = ObChecklist::factory()->create();

        $this->getJson("/api/v1/ob/checklists/{$theirs->id}")->assertForbidden();
        $this->getJson("/api/v1/ob/checklists/{$theirs->id}/photos")->assertForbidden();
    }

    /**
     * Reading data leaves no trace of its own, so the endpoint that hands out
     * signed links to evidence photos has to write one. Without this, the one
     * action that actually exposes the photos is the one Riwayat Aktivitas
     * cannot see.
     */
    public function test_opening_photos_is_recorded_in_the_activity_log(): void
    {
        $worker = $this->actingAsMobileUser(self::PERMISSIONS);
        $checklist = ObChecklist::factory()->create(['user_id' => $worker->id]);

        $this->getJson("/api/v1/ob/checklists/{$checklist->id}/photos")->assertOk();

        $entry = Activity::query()->where('log_name', 'akses-data')->latest('id')->first();

        $this->assertNotNull($entry);
        $this->assertSame('Lihat foto', $entry->description);
        $this->assertSame($checklist->id, $entry->subject_id);
    }

    public function test_a_retired_area_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create(['is_active' => false])->id,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('ob_area_id');
    }

    public function test_a_report_without_photos_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('photo_ids');
    }

    /**
     * A phone's clock is not evidence, but losing the report over a wrong one
     * would defeat the point of filing offline. Out-of-range values are
     * pulled into range instead of rejected.
     */
    public function test_an_impossible_submission_time_is_clamped_not_rejected(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [$this->upload()],
            'submitted_at' => now()->addYear()->toIso8601String(),
        ], $this->idempotencyHeader())->assertCreated();

        $submitted = ObChecklist::query()->sole()->submitted_at;

        $this->assertNotNull($submitted);
        $this->assertFalse($submitted->isFuture());
    }

    public function test_a_time_filed_offline_this_morning_is_kept(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $filedAt = now()->subHours(2);

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [$this->upload()],
            'submitted_at' => $filedAt->toIso8601String(),
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertSame(
            $filedAt->startOfSecond()->toIso8601String(),
            ObChecklist::query()->sole()->submitted_at->startOfSecond()->toIso8601String(),
        );
    }

    public function test_only_active_areas_are_offered(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $active = ObArea::factory()->create(['is_active' => true]);
        $retired = ObArea::factory()->create(['is_active' => false]);

        $names = collect($this->getJson('/api/v1/ob/areas')->json('data'))->pluck('id');

        $this->assertTrue($names->contains($active->id));
        $this->assertFalse($names->contains($retired->id));
    }

    /**
     * A worker who can file reports but holds no permission at all still
     * cannot reach the module.
     */
    public function test_a_worker_without_the_permission_is_refused(): void
    {
        $this->actingAsMobileUser('View:SecurityScan');

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertForbidden();
    }

    private function upload(): string
    {
        return $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');
    }
}
