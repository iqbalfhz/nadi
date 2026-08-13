<?php

namespace Tests\Feature;

use App\Enums\QueueTicketStatus;
use App\Filament\Widgets\QueueTicketsOverview;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class QueueTicketsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_only_todays_tickets(): void
    {
        $category = QueueCategory::factory()->create();
        $operator = User::factory()->create();

        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Waiting,
            'created_at' => now(),
        ]);

        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Done,
            'called_by' => $operator->id,
            'called_at' => now()->subMinutes(10),
            'done_at' => now(),
            'created_at' => now(),
        ]);

        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Skipped,
            'called_by' => $operator->id,
            'called_at' => now()->subMinutes(5),
            'done_at' => now(),
            'created_at' => now(),
        ]);

        // From yesterday — must not be counted in any of today's stats.
        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Done,
            'called_at' => now()->subDay(),
            'done_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        $stats = (new ReflectionMethod(QueueTicketsOverview::class, 'getStats'))
            ->invoke(new QueueTicketsOverview);

        [$total, $done, $skipped, $averageDuration] = $stats;

        $this->assertSame('3', $total->getValue());
        $this->assertSame('1', $done->getValue());
        $this->assertSame('1', $skipped->getValue());
        $this->assertSame('10 menit', $averageDuration->getValue());
    }

    public function test_the_average_duration_is_a_dash_when_nothing_has_been_done_today(): void
    {
        $category = QueueCategory::factory()->create();
        QueueTicket::factory()->create([
            'queue_category_id' => $category->id,
            'status' => QueueTicketStatus::Waiting,
            'created_at' => now(),
        ]);

        $stats = (new ReflectionMethod(QueueTicketsOverview::class, 'getStats'))
            ->invoke(new QueueTicketsOverview);

        [, , , $averageDuration] = $stats;

        $this->assertSame('—', $averageDuration->getValue());
    }
}
