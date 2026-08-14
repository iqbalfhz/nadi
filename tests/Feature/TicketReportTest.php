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

    public function test_any_authenticated_employee_can_view_the_app_ticket_report_with_no_role_assigned(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $employee = User::factory()->create();
        $this->actingAs($employee);

        $ticket = Ticket::factory()->create();

        Livewire::test(AppListTickets::class)
            ->assertCanSeeTableRecords([$ticket]);
    }

    public function test_the_app_report_shows_tickets_from_every_cashier_not_just_the_current_user(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $me = User::factory()->create();
        $someoneElse = User::factory()->create();
        $event = Event::factory()->create();

        $mine = Ticket::factory()->create(['event_id' => $event->id, 'sold_by' => $me->id]);
        $theirs = Ticket::factory()->create(['event_id' => $event->id, 'sold_by' => $someoneElse->id]);

        $this->actingAs($me);

        Livewire::test(AppListTickets::class)
            ->assertCanSeeTableRecords([$mine, $theirs]);
    }

    public function test_the_app_report_can_be_filtered_by_event(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs(User::factory()->create());

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
