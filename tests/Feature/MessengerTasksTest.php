<?php

namespace Tests\Feature;

use App\Enums\MessengerDeliveryStatus;
use App\Filament\App\Pages\MessengerTasks;
use App\Models\MessengerDelivery;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MessengerTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        Storage::fake('public');
    }

    public function test_a_messenger_can_claim_an_open_task(): void
    {
        $messenger = $this->actingAsEmployeeWithPermissions('View:MessengerTasks');

        $delivery = MessengerDelivery::factory()->create();

        Livewire::test(MessengerTasks::class)
            ->call('claim', $delivery->id);

        $delivery->refresh();

        $this->assertSame(MessengerDeliveryStatus::PickedUp, $delivery->status);
        $this->assertSame($messenger->id, $delivery->messenger_id);
        $this->assertNotNull($delivery->claimed_at);
    }

    public function test_two_messengers_cannot_claim_the_same_task(): void
    {
        $delivery = MessengerDelivery::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();

        MessengerDelivery::claim($delivery->id, $first);

        $this->expectException(\RuntimeException::class);
        MessengerDelivery::claim($delivery->id, $second);
    }

    public function test_a_messenger_can_progress_a_claimed_task_through_to_delivered_with_a_photo(): void
    {
        $messenger = $this->actingAsEmployeeWithPermissions('View:MessengerTasks');

        $delivery = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::PickedUp,
            'messenger_id' => $messenger->id,
            'claimed_at' => now(),
        ]);

        $component = Livewire::test(MessengerTasks::class)
            ->call('startTransit', $delivery->id);

        $this->assertSame(MessengerDeliveryStatus::InTransit, $delivery->fresh()->status);

        $component
            ->call('startCompleting', $delivery->id)
            ->assertSet('completingDeliveryId', $delivery->id)
            ->fillForm(['photo' => UploadedFile::fake()->image('bukti.jpg')])
            ->call('completeDelivery');

        $delivery->refresh();

        $this->assertSame(MessengerDeliveryStatus::Delivered, $delivery->status);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertCount(1, $delivery->getMedia('proof'));
    }

    public function test_a_messenger_cannot_advance_someone_elses_claimed_task(): void
    {
        $owner = User::factory()->create();
        $intruder = $this->actingAsEmployeeWithPermissions('View:MessengerTasks');

        $delivery = MessengerDelivery::factory()->create([
            'status' => MessengerDeliveryStatus::PickedUp,
            'messenger_id' => $owner->id,
            'claimed_at' => now(),
        ]);

        Livewire::test(MessengerTasks::class)
            ->call('startTransit', $delivery->id);

        $this->assertSame(MessengerDeliveryStatus::PickedUp, $delivery->fresh()->status);
    }
}
