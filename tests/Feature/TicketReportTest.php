<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Tickets\Pages\ListTickets as AppListTickets;
use App\Filament\Resources\Tickets\Pages\ListTickets as AdminListTickets;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_with_the_ticket_report_permission_can_view_the_app_ticket_report(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $employee = $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');

        $ticket = Ticket::factory()->create();

        Livewire::test(AppListTickets::class)
            ->assertCanSeeTableRecords([$ticket]);
    }

    public function test_the_app_report_shows_tickets_from_every_cashier_not_just_the_current_user(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');
        $event = Event::factory()->create();

        $mine = Ticket::factory()->create(['event_id' => $event->id, 'sold_by' => $me->id]);
        $theirs = Ticket::factory()->create(['event_id' => $event->id, 'sold_by' => $someoneElse->id]);

        Livewire::test(AppListTickets::class)
            ->assertCanSeeTableRecords([$mine, $theirs]);
    }

    public function test_the_app_report_can_be_filtered_by_event(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAsEmployeeWithPermissions('ViewAny:Ticket');

        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create();

        $ticketA = Ticket::factory()->create(['event_id' => $eventA->id]);
        $ticketB = Ticket::factory()->create(['event_id' => $eventB->id]);

        Livewire::test(AppListTickets::class)
            ->filterTable('event_id', $eventA->id)
            ->assertCanSeeTableRecords([$ticketA])
            ->assertCanNotSeeTableRecords([$ticketB]);
    }

    public function test_the_admin_report_shows_tickets_from_every_cashier_too(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAsSuperAdmin();

        $ticketA = Ticket::factory()->create();
        $ticketB = Ticket::factory()->create(['sold_by' => User::factory()->create()->id]);

        Livewire::test(AdminListTickets::class)
            ->assertCanSeeTableRecords([$ticketA, $ticketB]);
    }
}
