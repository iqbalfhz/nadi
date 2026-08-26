<?php

namespace Tests\Feature;

use App\Enums\MessengerDeliveryStatus;
use App\Enums\QueueTicketStatus;
use App\Filament\App\Widgets\DashboardStatsWidget;
use App\Models\MessengerDelivery;
use App\Models\ObChecklist;
use App\Models\QueueTicket;
use App\Models\RoomBooking;
use App\Models\SecurityPatrol;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VendorSale;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    /**
     * @return array<int, Stat>
     */
    private function getStats(): array
    {
        return (new ReflectionMethod(DashboardStatsWidget::class, 'getStats'))
            ->invoke(new DashboardStatsWidget);
    }

    public function test_it_returns_no_stats_when_the_user_has_none_of_the_relevant_permissions(): void
    {
        $this->actingAsEmployeeWithPermissions('View:GenerateBarcode');

        $this->assertSame([], $this->getStats());
    }

    public function test_it_only_shows_stats_the_user_has_permission_for(): void
    {
        $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');

        $stats = $this->getStats();

        $this->assertCount(1, $stats);
        $this->assertSame('Booking Saya Mendatang', $stats[0]->getLabel());
    }

    public function test_booking_stat_only_counts_this_users_upcoming_bookings(): void
    {
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');
        $someoneElse = User::factory()->create();

        RoomBooking::factory()->create(['user_id' => $me->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        RoomBooking::factory()->create(['user_id' => $me->id, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHour()]);
        RoomBooking::factory()->create(['user_id' => $someoneElse->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $stats = $this->getStats();

        $this->assertSame('1', $stats[0]->getValue());
    }

    public function test_ob_checklist_stat_only_counts_this_users_checklists_today(): void
    {
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:ObChecklist');
        $someoneElse = User::factory()->create();

        ObChecklist::factory()->create(['user_id' => $me->id]);
        ObChecklist::factory()->create(['user_id' => $me->id, 'created_at' => now()->subDay()]);
        ObChecklist::factory()->create(['user_id' => $someoneElse->id]);

        $stats = $this->getStats();

        $this->assertSame('1', $stats[0]->getValue());
    }

    public function test_messenger_stat_counts_only_this_users_active_tasks(): void
    {
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:MessengerDelivery');
        $someoneElse = User::factory()->create();

        MessengerDelivery::factory()->create(['messenger_id' => $me->id, 'status' => MessengerDeliveryStatus::PickedUp]);
        MessengerDelivery::factory()->create(['messenger_id' => $me->id, 'status' => MessengerDeliveryStatus::Delivered]);
        MessengerDelivery::factory()->create(['messenger_id' => $someoneElse->id, 'status' => MessengerDeliveryStatus::InTransit]);

        $stats = $this->getStats();

        $this->assertSame('1', $stats[0]->getValue());
    }

    public function test_queue_stat_counts_only_tickets_this_user_finished_today(): void
    {
        $me = $this->actingAsEmployeeWithPermissions('View:QueueOperator');
        $someoneElse = User::factory()->create();

        QueueTicket::factory()->create(['called_by' => $me->id, 'status' => QueueTicketStatus::Done, 'called_at' => now()]);
        QueueTicket::factory()->create(['called_by' => $me->id, 'status' => QueueTicketStatus::Waiting]);
        QueueTicket::factory()->create(['called_by' => $someoneElse->id, 'status' => QueueTicketStatus::Done, 'called_at' => now()]);

        $stats = $this->getStats();

        $this->assertSame('1', $stats[0]->getValue());
    }

    public function test_security_stat_only_counts_this_users_patrols_today(): void
    {
        $me = $this->actingAsEmployeeWithPermissions('View:SecurityScan');
        $someoneElse = User::factory()->create();

        SecurityPatrol::factory()->create(['user_id' => $me->id]);
        SecurityPatrol::factory()->create(['user_id' => $me->id, 'created_at' => now()->subDay()]);
        SecurityPatrol::factory()->create(['user_id' => $someoneElse->id]);

        $stats = $this->getStats();

        $this->assertSame('1', $stats[0]->getValue());
    }

    public function test_ticket_stat_counts_every_cashiers_sales_today_not_just_this_user(): void
    {
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');
        $someoneElse = User::factory()->create();

        Ticket::factory()->create(['sold_by' => $me->id]);
        Ticket::factory()->create(['sold_by' => $someoneElse->id]);
        Ticket::factory()->create(['sold_by' => $someoneElse->id, 'created_at' => now()->subDay()]);

        $stats = $this->getStats();

        $this->assertSame('2', $stats[0]->getValue());
    }

    public function test_vendor_sale_stat_counts_distinct_transactions_today_not_line_items(): void
    {
        $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');

        VendorSale::factory()->create(['transaction_number' => 'BZR-SHARED']);
        VendorSale::factory()->create(['transaction_number' => 'BZR-SHARED']);
        VendorSale::factory()->create(['transaction_number' => 'BZR-OTHER', 'created_at' => now()->subDay()]);

        $stats = $this->getStats();

        $this->assertSame('1', $stats[0]->getValue());
    }
}
