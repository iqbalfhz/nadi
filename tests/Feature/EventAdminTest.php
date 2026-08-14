<?php

namespace Tests\Feature;

use App\Enums\TicketPaymentMethod;
use App\Filament\App\Resources\Tickets\TicketResource as AppTicketResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_admin_can_create_an_event(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateEvent::class)
            ->fillForm([
                'name' => 'Nonton Bareng 2026',
                'regular_price' => 25000,
                'member_price' => 15000,
                'is_open' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EventResource::getUrl('index'));

        $this->assertDatabaseHas('events', [
            'name' => 'Nonton Bareng 2026',
            'regular_price' => 25000,
            'member_price' => 15000,
            'is_open' => false,
        ]);
    }

    public function test_an_admin_can_open_and_edit_an_events_prices(): void
    {
        $this->actingAsSuperAdmin();

        $event = Event::factory()->create(['is_open' => false, 'regular_price' => 25000]);

        Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
            ->fillForm([
                'regular_price' => 30000,
                'is_open' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'regular_price' => 30000,
            'is_open' => true,
        ]);
    }

    public function test_the_ticket_resource_has_no_create_capability(): void
    {
        $this->assertArrayNotHasKey('create', TicketResource::getPages());
    }

    public function test_an_admin_can_correct_a_tickets_mistaken_payment_method(): void
    {
        $this->actingAsSuperAdmin();

        $ticket = Ticket::factory()->create(['payment_method' => TicketPaymentMethod::Qris]);

        Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm([
                'payment_method' => TicketPaymentMethod::Cash->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(TicketPaymentMethod::Cash, $ticket->fresh()->payment_method);
    }

    public function test_the_app_ticket_resource_has_no_edit_capability(): void
    {
        $this->assertArrayNotHasKey('edit', AppTicketResource::getPages());
    }
}
