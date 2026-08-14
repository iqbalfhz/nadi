<?php

namespace Tests\Feature;

use App\Enums\TicketPaymentMethod;
use App\Filament\Widgets\TicketsOverview;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class TicketsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_the_open_events_ticket_sales(): void
    {
        $openEvent = Event::factory()->create(['is_open' => true]);
        $closedEvent = Event::factory()->create(['is_open' => false]);

        Ticket::factory()->create([
            'event_id' => $openEvent->id,
            'is_member' => true,
            'price' => 15000,
            'payment_method' => TicketPaymentMethod::Cash,
        ]);

        Ticket::factory()->create([
            'event_id' => $openEvent->id,
            'is_member' => false,
            'price' => 25000,
            'payment_method' => TicketPaymentMethod::Qris,
        ]);

        // Belongs to a different (closed) event — must not be counted.
        Ticket::factory()->create([
            'event_id' => $closedEvent->id,
            'price' => 25000,
        ]);

        $stats = (new ReflectionMethod(TicketsOverview::class, 'getStats'))
            ->invoke(new TicketsOverview);

        [$total, $revenue, $member, $regular, $cash, $qris, $edc] = $stats;

        $this->assertSame('2', $total->getValue());
        $this->assertSame('Rp 40.000', $revenue->getValue());
        $this->assertSame('1', $member->getValue());
        $this->assertSame('1', $regular->getValue());
        $this->assertSame('1', $cash->getValue());
        $this->assertSame('1', $qris->getValue());
        $this->assertSame('0', $edc->getValue());
    }

    public function test_it_does_not_crash_when_no_event_exists_at_all(): void
    {
        $stats = (new ReflectionMethod(TicketsOverview::class, 'getStats'))
            ->invoke(new TicketsOverview);

        [$total, , $member, $regular] = $stats;

        $this->assertSame('0', $total->getValue());
        $this->assertSame('0', $member->getValue());
        $this->assertSame('0', $regular->getValue());
    }

    public function test_it_falls_back_to_the_latest_event_when_none_is_open(): void
    {
        $older = Event::factory()->create(['is_open' => false, 'created_at' => now()->subDay()]);
        $latest = Event::factory()->create(['is_open' => false]);

        Ticket::factory()->create(['event_id' => $older->id, 'price' => 25000]);
        Ticket::factory()->create(['event_id' => $latest->id, 'price' => 15000]);

        $stats = (new ReflectionMethod(TicketsOverview::class, 'getStats'))
            ->invoke(new TicketsOverview);

        [$total] = $stats;

        $this->assertSame('1', $total->getValue());
    }
}
