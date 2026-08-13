<?php

namespace Tests\Feature;

use App\Enums\QueueTicketStatus;
use App\Filament\Resources\QueueCategories\Pages\CreateQueueCategory;
use App\Filament\Resources\QueueCategories\Pages\EditQueueCategory;
use App\Filament\Resources\QueueCategories\QueueCategoryResource;
use App\Filament\Resources\QueueTickets\Pages\ListQueueTickets;
use App\Filament\Resources\QueueTickets\QueueTicketResource;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueueAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_creating_a_queue_category_redirects_back_to_the_list(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateQueueCategory::class)
            ->fillForm([
                'name' => 'Resepsionis',
                'code' => 'a',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(QueueCategoryResource::getUrl('index'));

        $this->assertDatabaseHas('queue_categories', [
            'name' => 'Resepsionis',
            'code' => 'A',
        ]);
    }

    public function test_queue_category_code_is_stored_uppercase(): void
    {
        $this->actingAsSuperAdmin();
        $category = QueueCategory::factory()->create(['code' => 'A']);

        Livewire::test(EditQueueCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => $category->name,
                'code' => 'b',
                'is_active' => $category->is_active,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('B', $category->fresh()->code);
    }

    public function test_the_ticket_history_resource_has_no_create_capability(): void
    {
        $this->assertFalse(QueueTicketResource::canCreate());
        $this->assertArrayNotHasKey('create', QueueTicketResource::getPages());
    }

    public function test_an_admin_can_force_resolve_a_ticket_stuck_as_called(): void
    {
        $this->actingAsSuperAdmin();
        $category = QueueCategory::factory()->create();
        $ticket = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Called,
        ]);

        Livewire::test(ListQueueTickets::class)
            ->callTableAction('markDone', $ticket);

        $this->assertSame(QueueTicketStatus::Done, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->done_at);
    }

    public function test_the_force_resolve_actions_are_hidden_for_tickets_that_are_not_called(): void
    {
        $this->actingAsSuperAdmin();
        $category = QueueCategory::factory()->create();
        $ticket = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Waiting,
        ]);

        Livewire::test(ListQueueTickets::class)
            ->assertTableActionHidden('markDone', $ticket)
            ->assertTableActionHidden('markSkipped', $ticket);
    }

    public function test_the_ticket_history_defaults_to_showing_only_todays_tickets(): void
    {
        $this->actingAsSuperAdmin();
        $category = QueueCategory::factory()->create();
        $today = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'created_at' => now(),
        ]);
        $yesterday = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(ListQueueTickets::class)
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$yesterday]);
    }

    public function test_widening_the_date_filter_reveals_older_tickets(): void
    {
        $this->actingAsSuperAdmin();
        $category = QueueCategory::factory()->create();
        $yesterday = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(ListQueueTickets::class)
            ->assertCanNotSeeTableRecords([$yesterday])
            ->filterTable('created_at', ['from' => now()->subDays(2)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$yesterday]);
    }
}
