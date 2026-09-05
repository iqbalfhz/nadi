<?php

namespace Tests\Feature\Api;

use App\Enums\MessengerDeliveryStatus;
use App\Models\HkCategory;
use App\Models\HkInspection;
use App\Models\MessengerDelivery;
use App\Models\ObArea;
use App\Models\ObChecklist;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Promises docs/API-MOBILE.md makes that no single module's tests cover.
 *
 * Every case here comes from a question the Flutter side actually had to ask,
 * because the answer could not be established from the client. Answering in
 * chat would have left the next person to ask again; these pin the answers to
 * the build.
 */
class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_FIELD_PERMISSIONS = [
        'ViewAny:ObChecklist', 'Create:ObChecklist',
        'View:SecurityScan',
        'ViewAny:HkInspection', 'Create:HkInspection',
        'View:MessengerTasks',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
    }

    /**
     * §4 promises a photo response shape, and the doc claims all four modules
     * share it. Messenger was the doubtful one: its collection is singleFile
     * and the path is /proof rather than /photos, so the client had to guess
     * whether it returns an object instead of an array. It does not.
     */
    public function test_every_photo_endpoint_returns_the_same_shape(): void
    {
        $worker = $this->actingAsMobileUser(self::ALL_FIELD_PERMISSIONS);

        $ob = ObChecklist::factory()->create(['user_id' => $worker->id]);
        $patrol = SecurityPatrol::factory()->create(['user_id' => $worker->id]);
        $hk = HkInspection::factory()->create(['user_id' => $worker->id]);
        $delivery = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::Delivered,
            'messenger_id' => $worker->id,
        ]);

        $endpoints = [
            "/api/v1/ob/checklists/{$ob->id}/photos",
            "/api/v1/security/patrols/{$patrol->id}/photos",
            "/api/v1/hk/inspections/{$hk->id}/photos",
            "/api/v1/messenger/tasks/{$delivery->id}/proof",
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);

            $response->assertOk();

            // An array every time, including the singleFile proof collection.
            $this->assertIsArray($response->json('data'), $endpoint);
        }
    }

    /**
     * §6 says master data comes back as a plain array with no meta. The app
     * caches the whole thing and files reports offline against that cache, so
     * paginating either list later would silently leave every area past the
     * first page unpickable — with nothing on the phone to notice it.
     */
    public function test_master_data_is_never_paginated(): void
    {
        $this->actingAsMobileUser(self::ALL_FIELD_PERMISSIONS);

        ObArea::factory()->count(25)->create(['is_active' => true]);
        HkCategory::factory()->count(25)->create(['is_active' => true]);

        foreach (['/api/v1/ob/areas', '/api/v1/hk/categories'] as $endpoint) {
            $response = $this->getJson($endpoint);

            $response->assertOk();

            $this->assertCount(25, $response->json('data'), $endpoint);
            $this->assertNull($response->json('meta'), $endpoint);
        }
    }

    /**
     * A guard standing at a post retired this morning must not be told the
     * same thing as one who scanned a sticker from another building. The first
     * should stop and report it; the second should rescan.
     */
    public function test_an_unknown_code_and_a_retired_post_are_told_apart(): void
    {
        $this->actingAsMobileUser('View:SecurityScan');

        $retired = SecurityCheckpoint::factory()->create(['is_active' => false]);

        $unknown = $this->getJson('/api/v1/security/checkpoints/'.str_repeat('x', 32));
        $gone = $this->getJson("/api/v1/security/checkpoints/{$retired->code}");

        $unknown->assertNotFound();
        $gone->assertStatus(410);

        $this->assertNotSame($unknown->json('message'), $gone->json('message'));
    }

    /**
     * A courier whose reply was lost, or whose app restarted before the key
     * was persisted, re-taps Ambil. Told "sudah diambil messenger lain" they
     * would stop looking for a document sitting in their own hands.
     */
    public function test_reclaiming_your_own_task_says_so(): void
    {
        $courier = $this->actingAsMobileUser('View:MessengerTasks');
        $delivery = MessengerDelivery::factory()->create(['status' => MessengerDeliveryStatus::Available]);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $this->idempotencyHeader())
            ->assertOk();

        // A fresh key, as a restarted app would send.
        $again = $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $this->idempotencyHeader());

        $again->assertStatus(409);
        $this->assertSame('Tugas ini sudah Anda ambil. Ada di daftar Tugas Saya.', $again->json('message'));
    }

    public function test_a_task_taken_by_someone_else_still_says_that(): void
    {
        $this->actingAsMobileUser('View:MessengerTasks');

        $theirs = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::PickedUp,
            'messenger_id' => User::factory()->create()->id,
        ]);

        $response = $this->postJson("/api/v1/messenger/tasks/{$theirs->id}/claim", [], $this->idempotencyHeader());

        $response->assertStatus(409);
        $this->assertSame('Tugas ini sudah diambil messenger lain.', $response->json('message'));
    }

    /**
     * The same key on the same claim replays the original success, so a
     * dropped reply the app *can* retry never reaches the 409 path above.
     */
    public function test_reclaiming_with_the_same_key_replays_the_success(): void
    {
        $this->actingAsMobileUser('View:MessengerTasks');
        $delivery = MessengerDelivery::factory()->create(['status' => MessengerDeliveryStatus::Available]);

        $headers = $this->idempotencyHeader();

        $first = $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $headers);
        $second = $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $headers);

        $first->assertOk();
        $second->assertOk()->assertHeader('Idempotency-Replayed', 'true');
    }

    /**
     * §3 said "every POST", which sent the Flutter side hunting for a 400 that
     * never comes. Login is outside the idempotency group by design: there is
     * no record to duplicate, and a phone that cannot sign in has no key to
     * reuse anyway.
     */
    public function test_login_does_not_require_an_idempotency_key(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'tidak-ada@tangcity.com',
            'password' => 'salah',
            'device_name' => 'Redmi Note 12',
        ])->assertStatus(422);
    }

    /**
     * An outbox coming back from a basement flushes a whole shift at once. The
     * ceiling exists to stop a runaway client, not to pace normal use — if it
     * ever bites a legitimate flush, it is set wrong.
     */
    public function test_a_full_shift_flushing_at_once_is_not_throttled(): void
    {
        $this->actingAsMobileUser(self::ALL_FIELD_PERMISSIONS);

        $area = ObArea::factory()->create(['is_active' => true]);

        // Twelve reports with one photo each: 24 requests back to back.
        for ($report = 0; $report < 12; $report++) {
            $photo = $this->postJson('/api/v1/uploads', [
                'photo' => UploadedFile::fake()->image('bukti.jpg'),
            ], $this->idempotencyHeader());

            $photo->assertCreated();

            $this->postJson('/api/v1/ob/checklists', [
                'ob_area_id' => $area->id,
                'photo_ids' => [$photo->json('data.id')],
            ], $this->idempotencyHeader())->assertCreated();
        }
    }

    /**
     * A courier who cannot see where to collect cannot start. The module
     * shipped without an origin at all — the same hole exists on the web,
     * and a phone is simply where it finally showed.
     */
    public function test_a_task_says_where_to_collect_and_who_asked(): void
    {
        $this->actingAsMobileUser('View:MessengerTasks');

        $requester = User::factory()->create(['name' => 'Sinta']);

        MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::Available,
            'sender_id' => $requester->id,
            'origin' => 'Front Office Lt 1',
        ]);

        $this->getJson('/api/v1/messenger/tasks/open')
            ->assertOk()
            ->assertJsonPath('data.0.origin', 'Front Office Lt 1')
            ->assertJsonPath('data.0.requester.name', 'Sinta');
    }
}
