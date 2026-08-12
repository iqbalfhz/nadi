<?php

namespace Tests\Feature;

use App\Enums\QueueTicketStatus;
use App\Events\QueueNumberCalled;
use App\Filament\App\Pages\QueueOperator;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class QueueOperatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_an_employee_can_call_the_next_waiting_ticket(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', 'Loket 1')
            ->call('callNext');

        $this->assertSame(QueueTicketStatus::Called, $ticket->fresh()->status);
        Event::assertDispatched(QueueNumberCalled::class);
    }

    public function test_calling_next_is_a_no_op_without_a_counter_label(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', '')
            ->call('callNext');

        $this->assertSame(QueueTicketStatus::Waiting, $ticket->fresh()->status);
    }

    public function test_cannot_call_next_while_a_ticket_is_already_active(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        QueueTicket::createNextFor($category);
        $second = QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', 'Loket 1')
            ->call('callNext')
            ->call('callNext');

        $this->assertSame(QueueTicketStatus::Waiting, $second->fresh()->status);
    }

    public function test_marking_the_current_ticket_done_frees_up_the_operator(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', 'Loket 1')
            ->call('callNext')
            ->call('markDone')
            ->assertSet('currentTicketId', null);

        $this->assertSame(QueueTicketStatus::Done, $ticket->fresh()->status);
    }

    public function test_marking_the_current_ticket_skipped(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', 'Loket 1')
            ->call('callNext')
            ->call('markSkipped');

        $this->assertSame(QueueTicketStatus::Skipped, $ticket->fresh()->status);
    }

    public function test_recalling_rebroadcasts_the_current_ticket(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', 'Loket 1')
            ->call('callNext')
            ->call('recall');

        Event::assertDispatched(QueueNumberCalled::class, 2);
    }
}
