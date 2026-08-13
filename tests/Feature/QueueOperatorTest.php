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

    public function test_calling_next_without_a_counter_label_notifies_instead_of_silently_doing_nothing(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', '')
            ->call('callNext')
            ->assertNotified('Isi dulu Nama Loket / Counter Anda.');

        $this->assertSame(QueueTicketStatus::Waiting, $ticket->fresh()->status);
    }

    public function test_calling_next_with_nothing_waiting_notifies_instead_of_silently_doing_nothing(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->set('categoryId', $category->id)
            ->set('counterLabel', 'Loket 1')
            ->call('callNext')
            ->assertNotified('Tidak ada lagi yang menunggu di loket ini.');
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
            ->call('callNext')
            ->assertNotified('Selesaikan dulu tiket yang sedang dilayani sebelum memanggil berikutnya.');

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

    public function test_a_page_refresh_restores_the_operators_in_progress_ticket(): void
    {
        $user = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);
        QueueTicket::callNextFor($category, $user, 'Loket 2');

        // Simulating a refresh: a fresh component instance re-runs mount()
        // instead of reusing in-memory state from the previous one.
        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->assertSet('categoryId', $category->id)
            ->assertSet('currentTicketId', $ticket->id)
            ->assertSet('counterLabel', 'Loket 2');
    }

    public function test_mount_does_not_restore_a_ticket_claimed_by_a_different_operator(): void
    {
        $user = User::factory()->create();
        $otherOperator = User::factory()->create();
        $category = QueueCategory::factory()->create(['is_active' => true]);
        QueueTicket::createNextFor($category);
        QueueTicket::callNextFor($category, $otherOperator, 'Loket 3');

        Livewire::actingAs($user)
            ->test(QueueOperator::class)
            ->assertSet('currentTicketId', null);
    }
}
