<?php

namespace Tests\Feature\Api;

use App\Enums\MessengerDeliveryStatus;
use App\Models\MessengerDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tugas Messenger over the API — the only field module with a state machine.
 */
class MessengerTaskApiTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = 'View:MessengerTasks';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
    }

    public function test_open_tasks_are_visible_to_every_courier(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $open = MessengerDelivery::factory()->create(['status' => MessengerDeliveryStatus::Available]);
        $taken = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::PickedUp,
            'messenger_id' => User::factory()->create()->id,
        ]);

        $ids = collect($this->getJson('/api/v1/messenger/tasks/open')->json('data'))->pluck('id');

        // Deliberately not scoped: self-pickup means seeing what nobody
        // has taken yet, including tasks raised by other people.
        $this->assertTrue($ids->contains($open->id));
        $this->assertFalse($ids->contains($taken->id));
    }

    public function test_a_courier_can_walk_a_task_through_to_delivery(): void
    {
        $courier = $this->actingAsMobileUser(self::PERMISSION);
        $delivery = MessengerDelivery::factory()->create(['status' => MessengerDeliveryStatus::Available]);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $this->idempotencyHeader())
            ->assertOk()
            ->assertJsonPath('data.status', MessengerDeliveryStatus::PickedUp->value);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/transit", [], $this->idempotencyHeader())
            ->assertOk()
            ->assertJsonPath('data.status', MessengerDeliveryStatus::InTransit->value);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/deliver", [
            'photo_id' => $this->upload(),
        ], $this->idempotencyHeader())
            ->assertOk()
            ->assertJsonPath('data.status', MessengerDeliveryStatus::Delivered->value);

        $delivery->refresh();

        $this->assertSame($courier->id, $delivery->messenger_id);
        $this->assertSame(1, $delivery->getMedia('proof')->count());
        $this->assertNotNull($delivery->delivered_at);
    }

    /**
     * The reason claiming may not be queued offline: two couriers can tap the
     * same task, and the loser has to be told rather than left believing they
     * have it.
     */
    public function test_a_task_already_taken_is_refused(): void
    {
        $first = $this->actingAsMobileUser(self::PERMISSION);
        $delivery = MessengerDelivery::factory()->create(['status' => MessengerDeliveryStatus::Available]);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $this->idempotencyHeader())
            ->assertOk();

        $this->forgetAuthenticatedUser();
        $this->actingAsMobileUser(self::PERMISSION);

        $response = $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/claim", [], $this->idempotencyHeader());

        $response->assertStatus(409);

        // The refusal is a sentence a courier can act on, not a stack trace.
        $this->assertSame('Tugas ini sudah diambil messenger lain.', $response->json('message'));

        $this->assertSame($first->id, $delivery->refresh()->messenger_id);
    }

    public function test_a_task_cannot_be_delivered_before_it_leaves(): void
    {
        $courier = $this->actingAsMobileUser(self::PERMISSION);

        $delivery = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::PickedUp,
            'messenger_id' => $courier->id,
        ]);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/deliver", [
            'photo_id' => $this->upload(),
        ], $this->idempotencyHeader())->assertStatus(409);

        $this->assertSame(MessengerDeliveryStatus::PickedUp, $delivery->refresh()->status);
    }

    public function test_another_couriers_task_cannot_be_advanced(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $theirs = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::PickedUp,
            'messenger_id' => User::factory()->create()->id,
        ]);

        $this->postJson("/api/v1/messenger/tasks/{$theirs->id}/transit", [], $this->idempotencyHeader())
            ->assertStatus(409);
    }

    /**
     * Delivery without proof is a claim, not a record.
     */
    public function test_delivery_requires_a_proof_photo(): void
    {
        $courier = $this->actingAsMobileUser(self::PERMISSION);

        $delivery = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::InTransit,
            'messenger_id' => $courier->id,
        ]);

        $this->postJson("/api/v1/messenger/tasks/{$delivery->id}/deliver", [], $this->idempotencyHeader())
            ->assertJsonValidationErrors('photo_id');

        $this->assertSame(MessengerDeliveryStatus::InTransit, $delivery->refresh()->status);
    }

    public function test_my_tasks_shows_only_what_this_courier_carries(): void
    {
        $courier = $this->actingAsMobileUser(self::PERMISSION);

        $mine = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::InTransit,
            'messenger_id' => $courier->id,
            'claimed_at' => now(),
        ]);
        $theirs = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::InTransit,
            'messenger_id' => User::factory()->create()->id,
            'claimed_at' => now(),
        ]);
        $done = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::Delivered,
            'messenger_id' => $courier->id,
            'claimed_at' => now(),
        ]);

        $ids = collect($this->getJson('/api/v1/messenger/tasks/mine')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));

        // Finished work drops off the carrying list.
        $this->assertFalse($ids->contains($done->id));
    }

    public function test_another_couriers_proof_cannot_be_opened(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $theirs = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::Delivered,
            'messenger_id' => User::factory()->create()->id,
        ]);

        $this->getJson("/api/v1/messenger/tasks/{$theirs->id}/proof")->assertForbidden();
    }

    public function test_an_employee_without_the_permission_is_refused(): void
    {
        $this->actingAsMobileUser('Create:ObChecklist');

        $this->getJson('/api/v1/messenger/tasks/open')->assertForbidden();
    }

    private function upload(): string
    {
        return $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');
    }
}
