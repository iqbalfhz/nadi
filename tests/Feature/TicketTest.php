<?php

namespace Tests\Feature;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_price_applies_when_not_a_member(): void
    {
        $event = Event::factory()->create(['regular_price' => 25000, 'member_price' => 15000, 'is_open' => true]);
        $cashier = User::factory()->create();

        $ticket = Ticket::sellFor($event, $cashier, 'Budi', false, null, TicketPaymentMethod::Cash);

        $this->assertSame(25000, $ticket->price);
    }

    public function test_member_price_applies_when_a_member(): void
    {
        $event = Event::factory()->create(['regular_price' => 25000, 'member_price' => 15000, 'is_open' => true]);
        $cashier = User::factory()->create();

        $ticket = Ticket::sellFor($event, $cashier, 'Siti', true, 'CARD-001', TicketPaymentMethod::Qris);

        $this->assertSame(15000, $ticket->price);
        $this->assertSame('CARD-001', $ticket->member_reference);
    }

    public function test_price_is_snapshotted_and_unaffected_by_later_event_price_changes(): void
    {
        $event = Event::factory()->create(['regular_price' => 25000, 'member_price' => 15000, 'is_open' => true]);
        $cashier = User::factory()->create();

        $ticket = Ticket::sellFor($event, $cashier, 'Budi', false, null, TicketPaymentMethod::Cash);

        $event->update(['regular_price' => 50000]);

        $this->assertSame(25000, $ticket->fresh()->price);
    }

    public function test_a_transaction_number_is_auto_generated_and_unique(): void
    {
        $event = Event::factory()->create(['is_open' => true]);
        $cashier = User::factory()->create();

        $one = Ticket::sellFor($event, $cashier, 'Budi', false, null, TicketPaymentMethod::Cash);
        $two = Ticket::sellFor($event, $cashier, 'Siti', false, null, TicketPaymentMethod::Cash);

        $this->assertNotEmpty($one->transaction_number);
        $this->assertNotEmpty($two->transaction_number);
        $this->assertNotSame($one->transaction_number, $two->transaction_number);
    }

    public function test_selling_for_a_closed_event_fails_and_creates_no_ticket(): void
    {
        $event = Event::factory()->create(['is_open' => false]);
        $cashier = User::factory()->create();

        $this->expectException(RuntimeException::class);

        try {
            Ticket::sellFor($event, $cashier, 'Budi', false, null, TicketPaymentMethod::Cash);
        } finally {
            $this->assertDatabaseCount('tickets', 0);
        }
    }
}
