<?php

namespace Tests\Feature\Api;

use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Patroli Security over the API.
 *
 * The interesting difference from Checklist OB is what is deliberately
 * missing: no endpoint hands out the set of checkpoint codes.
 */
class SecurityPatrolApiTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = 'View:SecurityScan';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
        Storage::fake('public');
    }

    public function test_a_guard_can_file_a_patrol_from_a_scanned_code(): void
    {
        $guard = $this->actingAsMobileUser(self::PERMISSION);
        $checkpoint = SecurityCheckpoint::factory()->create(['is_active' => true]);

        $response = $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => $checkpoint->code,
            'incident_report' => 'Pintu darurat lantai 3 tidak terkunci.',
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader());

        $response->assertCreated();

        $patrol = SecurityPatrol::query()->sole();

        $this->assertSame($checkpoint->id, $patrol->security_checkpoint_id);
        $this->assertSame($guard->id, $patrol->user_id);
        $this->assertSame('Pintu darurat lantai 3 tidak terkunci.', $patrol->incident_report);
        $this->assertSame(1, $patrol->getMedia('photos')->count());
    }

    public function test_a_patrol_without_an_incident_is_fine(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => SecurityCheckpoint::factory()->create()->code,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertNull(SecurityPatrol::query()->sole()->incident_report);
    }

    public function test_a_scanned_code_resolves_to_its_checkpoint_name(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $checkpoint = SecurityCheckpoint::factory()->create(['name' => 'Pos Parkir P2']);

        $this->getJson("/api/v1/security/checkpoints/{$checkpoint->code}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Pos Parkir P2');
    }

    /**
     * The code is what proves the guard stood at the post. Echoing it back
     * would invite the app to accumulate the set, which is the one thing a
     * handset must not hold.
     */
    public function test_resolving_a_code_never_returns_the_code(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $checkpoint = SecurityCheckpoint::factory()->create();

        $response = $this->getJson("/api/v1/security/checkpoints/{$checkpoint->code}");

        $response->assertOk();
        $this->assertArrayNotHasKey('code', $response->json('data'));
    }

    /**
     * The guard rail for the whole design: there must be no way to ask the
     * API for every checkpoint at once. If one ever appears, a patrol round
     * can be filed from the canteen.
     */
    public function test_there_is_no_endpoint_listing_every_checkpoint(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        SecurityCheckpoint::factory()->count(3)->create();

        $this->getJson('/api/v1/security/checkpoints')->assertNotFound();
    }

    public function test_a_retired_checkpoint_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $retired = SecurityCheckpoint::factory()->create(['is_active' => false]);

        $this->getJson("/api/v1/security/checkpoints/{$retired->code}")->assertNotFound();

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => $retired->code,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('checkpoint_code');
    }

    public function test_an_unknown_code_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => str_repeat('x', 32),
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('checkpoint_code');

        $this->assertSame(0, SecurityPatrol::query()->count());
    }

    public function test_a_patrol_without_photos_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => SecurityCheckpoint::factory()->create()->code,
            'photo_ids' => [],
        ], $this->idempotencyHeader())->assertJsonValidationErrors('photo_ids');
    }

    public function test_the_list_only_shows_the_guards_own_rounds(): void
    {
        $guard = $this->actingAsMobileUser(self::PERMISSION);

        $mine = SecurityPatrol::factory()->create(['user_id' => $guard->id]);
        $theirs = SecurityPatrol::factory()->create();

        $ids = collect($this->getJson('/api/v1/security/patrols')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }

    public function test_another_guards_photos_cannot_be_opened(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $theirs = SecurityPatrol::factory()->create();

        $this->getJson("/api/v1/security/patrols/{$theirs->id}/photos")->assertForbidden();
    }

    public function test_opening_photos_is_recorded_in_the_activity_log(): void
    {
        $guard = $this->actingAsMobileUser(self::PERMISSION);
        $patrol = SecurityPatrol::factory()->create(['user_id' => $guard->id]);

        $this->getJson("/api/v1/security/patrols/{$patrol->id}/photos")->assertOk();

        $entry = Activity::query()->where('log_name', 'akses-data')->latest('id')->first();

        $this->assertNotNull($entry);
        $this->assertSame($patrol->id, $entry->subject_id);
    }

    /**
     * A patrol round's value is its times, and stairwells are exactly where
     * signal is not.
     */
    public function test_the_time_the_guard_reached_the_post_is_kept(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $reachedAt = now()->subMinutes(90);

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => SecurityCheckpoint::factory()->create()->code,
            'photo_ids' => [$this->upload()],
            'submitted_at' => $reachedAt->toIso8601String(),
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertSame(
            $reachedAt->startOfSecond()->toIso8601String(),
            SecurityPatrol::query()->sole()->submitted_at->startOfSecond()->toIso8601String(),
        );
    }

    /**
     * SecurityPatrolPolicy has no owner clause and guards hold no
     * Create:SecurityPatrol — the page permission is the real gate, exactly
     * as on the web.
     */
    public function test_an_employee_without_the_page_permission_is_refused(): void
    {
        $this->actingAsMobileUser('Create:ObChecklist');

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => SecurityCheckpoint::factory()->create()->code,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertForbidden();

        $this->getJson('/api/v1/security/patrols')->assertForbidden();
    }

    private function upload(): string
    {
        return $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');
    }
}
