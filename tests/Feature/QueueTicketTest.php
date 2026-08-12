<?php

namespace Tests\Feature;

use App\Enums\QueueTicketStatus;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_the_next_ticket_assigns_sequential_numbers_per_category(): void
    {
        $category = QueueCategory::factory()->create(['code' => 'A']);

        $first = QueueTicket::createNextFor($category);
        $second = QueueTicket::createNextFor($category);

        $this->assertSame(1, $first->number);
        $this->assertSame(2, $second->number);
        $this->assertSame('A001', $first->formatted_number);
        $this->assertSame('A002', $second->formatted_number);
    }

    public function test_ticket_numbering_is_independent_per_category(): void
    {
        $categoryA = QueueCategory::factory()->create(['code' => 'A']);
        $categoryB = QueueCategory::factory()->create(['code' => 'B']);

        QueueTicket::createNextFor($categoryA);
        $firstB = QueueTicket::createNextFor($categoryB);

        $this->assertSame(1, $firstB->number);
    }

    public function test_ticket_numbering_resets_for_a_new_day(): void
    {
        $category = QueueCategory::factory()->create(['code' => 'A']);

        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'number' => 7,
            'created_at' => now()->subDay(),
        ]);

        $ticket = QueueTicket::createNextFor($category);

        $this->assertSame(1, $ticket->number);
    }

    public function test_calling_next_claims_the_oldest_waiting_ticket(): void
    {
        $category = QueueCategory::factory()->create();
        $operator = User::factory()->create();

        $older = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'number' => 1,
            'status' => QueueTicketStatus::Waiting,
        ]);
        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'number' => 2,
            'status' => QueueTicketStatus::Waiting,
        ]);

        $called = QueueTicket::callNextFor($category, $operator, 'Loket 1');

        $this->assertSame($older->id, $called?->id);
        $this->assertSame(QueueTicketStatus::Called, $called->status);
        $this->assertSame('Loket 1', $called->counter_label);
        $this->assertSame($operator->id, $called->called_by);
        $this->assertNotNull($called->called_at);
    }

    public function test_calling_next_returns_null_when_nothing_is_waiting(): void
    {
        $category = QueueCategory::factory()->create();
        $operator = User::factory()->create();

        $this->assertNull(QueueTicket::callNextFor($category, $operator, 'Loket 1'));
    }

    public function test_calling_next_ignores_tickets_already_called_or_done(): void
    {
        $category = QueueCategory::factory()->create();
        $operator = User::factory()->create();

        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Called,
        ]);
        $waiting = QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Waiting,
        ]);

        $called = QueueTicket::callNextFor($category, $operator, 'Loket 1');

        $this->assertSame($waiting->id, $called?->id);
    }
}
