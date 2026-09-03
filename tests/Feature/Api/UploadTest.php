<?php

namespace Tests\Feature\Api;

use App\Models\ApiUpload;
use App\Models\ObArea;
use App\Models\ObChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The staging step that makes photo upload survivable on mall wifi.
 */
class UploadTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ['ViewAny:ObChecklist', 'Create:ObChecklist'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
        Storage::fake('public');
    }

    public function test_a_photo_is_staged_on_the_private_disk(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $response = $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader());

        $response->assertCreated()->assertJsonStructure(['data' => ['id', 'expires_at']]);

        $upload = ApiUpload::query()->sole();

        Storage::disk('internal')->assertExists($upload->path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        ], $this->idempotencyHeader())->assertJsonValidationErrors('photo');

        $this->assertSame(0, ApiUpload::query()->count());
    }

    /**
     * The same 10 MB ceiling the web forms carry. Restating it is the point:
     * the phone is a second doorway into the same collections.
     */
    public function test_an_oversized_photo_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('besar.jpg')->size(10241),
        ], $this->idempotencyHeader())->assertJsonValidationErrors('photo');
    }

    /**
     * An upload id is not a capability. Without the ownership scope, knowing
     * someone else's id would be enough to pull their photo into your own
     * report — and the audit trail would show you filed it.
     */
    public function test_another_workers_upload_cannot_be_claimed(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $theirs = $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');

        $this->forgetAuthenticatedUser();
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [$theirs],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('photo_ids.0');

        $this->assertSame(0, ObChecklist::query()->count());
    }

    /**
     * A claimed photo leaves staging entirely — row and file. Otherwise every
     * report would double the storage it needs and the nightly cleanup would
     * eventually delete a file the report still points at.
     */
    public function test_claiming_a_photo_clears_it_from_staging(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $id = $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');

        $stagedPath = ApiUpload::query()->sole()->path;

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create()->id,
            'photo_ids' => [$id],
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertSame(0, ApiUpload::query()->count());
        Storage::disk('internal')->assertMissing($stagedPath);

        // And the photo really did arrive in the report.
        $this->assertSame(1, ObChecklist::query()->sole()->getMedia('photos')->count());
    }
}
