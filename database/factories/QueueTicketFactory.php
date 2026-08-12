<?php

namespace Database\Factories;

use App\Enums\QueueTicketStatus;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueTicket>
 */
class QueueTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'queue_category_id' => QueueCategory::factory(),
            'number' => fake()->unique()->numberBetween(1, 999),
            'status' => QueueTicketStatus::Waiting,
        ];
    }
}
