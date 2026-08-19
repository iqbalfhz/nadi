<?php

namespace Tests\Feature;

use App\Enums\MessengerDeliveryStatus;
use App\Filament\App\Resources\MessengerDeliveries\Pages\CreateMessengerDelivery;
use App\Filament\App\Resources\MessengerDeliveries\Pages\ListMessengerDeliveries;
use App\Filament\Resources\MessengerDeliveries\Pages\ListMessengerDeliveries as AdminListMessengerDeliveries;
use App\Models\MessengerDelivery;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessengerDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_can_submit_a_delivery_request(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $sender = $this->actingAsEmployeeWithPermissions(['ViewAny:MessengerDelivery', 'Create:MessengerDelivery']);

        Livewire::test(CreateMessengerDelivery::class)
            ->fillForm([
                'destination' => 'Departemen Finance',
                'document_description' => 'Invoice bulan Agustus',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $delivery = MessengerDelivery::query()->where('destination', 'Departemen Finance')->firstOrFail();

        $this->assertSame($sender->id, $delivery->sender_id);
        $this->assertNotEmpty($delivery->tracking_number);
        $this->assertSame(MessengerDeliveryStatus::Available, $delivery->status);
    }

    public function test_the_sender_list_only_shows_their_own_deliveries(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:MessengerDelivery');

        $mine = MessengerDelivery::factory()->create(['sender_id' => $me->id]);
        $theirs = MessengerDelivery::factory()->create(['sender_id' => $someoneElse->id]);

        Livewire::test(ListMessengerDeliveries::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_the_admin_report_shows_deliveries_from_every_sender(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAsSuperAdmin();

        $deliveryA = MessengerDelivery::factory()->create();
        $deliveryB = MessengerDelivery::factory()->create(['sender_id' => User::factory()->create()->id]);

        Livewire::test(AdminListMessengerDeliveries::class)
            ->assertCanSeeTableRecords([$deliveryA, $deliveryB]);
    }
}
