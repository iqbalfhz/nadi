<?php

namespace Tests\Feature;

use App\Enums\TicketPaymentMethod;
use App\Filament\App\Pages\SellTicket;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SellTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_a_cashier_can_sell_a_ticket(): void
    {
        $cashier = User::factory()->create();
        $this->actingAs($cashier);

        $event = Event::factory()->create([
            'regular_price' => 25000,
            'member_price' => 15000,
            'is_open' => true,
        ]);

        Livewire::test(SellTicket::class)
            ->set('eventId', $event->id)
            ->set('buyerName', 'Budi Santoso')
            ->set('isMember', false)
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell');

        $ticket = Ticket::query()->where('event_id', $event->id)->firstOrFail();

        $this->assertSame('Budi Santoso', $ticket->buyer_name);
        $this->assertSame(25000, $ticket->price);
        $this->assertSame($cashier->id, $ticket->sold_by);
        $this->assertFalse($ticket->is_member);
    }

    public function test_member_pricing_applies_when_toggled(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create([
            'regular_price' => 25000,
            'member_price' => 15000,
            'is_open' => true,
        ]);

        Livewire::test(SellTicket::class)
            ->set('eventId', $event->id)
            ->set('buyerName', 'Siti')
            ->set('isMember', true)
            ->set('memberReference', 'CARD-001')
            ->set('paymentMethod', TicketPaymentMethod::Qris->value)
            ->call('sell');

        $ticket = Ticket::query()->where('event_id', $event->id)->firstOrFail();

        $this->assertSame(15000, $ticket->price);
        $this->assertTrue($ticket->is_member);
        $this->assertSame('CARD-001', $ticket->member_reference);
    }

    public function test_only_open_events_appear_in_the_dropdown(): void
    {
        $this->actingAs(User::factory()->create());

        $open = Event::factory()->create(['is_open' => true]);
        $closed = Event::factory()->create(['is_open' => false]);

        $component = Livewire::test(SellTicket::class);

        $this->assertTrue($component->get('openEvents')->contains('id', $open->id));
        $this->assertFalse($component->get('openEvents')->contains('id', $closed->id));
    }

    public function test_selling_as_member_without_a_barcode_does_not_create_a_ticket(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['is_open' => true]);

        Livewire::test(SellTicket::class)
            ->set('eventId', $event->id)
            ->set('buyerName', 'Siti')
            ->set('isMember', true)
            ->set('memberReference', '')
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_selling_without_an_event_does_not_create_a_ticket(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SellTicket::class)
            ->set('buyerName', 'Budi')
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_next_sale_clears_the_receipt(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['is_open' => true]);

        $component = Livewire::test(SellTicket::class)
            ->set('eventId', $event->id)
            ->set('buyerName', 'Budi')
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell')
            ->assertSet('lastTicketId', fn (?int $id) => $id !== null);

        $component->call('nextSale')->assertSet('lastTicketId', null);
    }
}
