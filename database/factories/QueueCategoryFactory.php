<?php

namespace Database\Factories;

use App\Models\QueueCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueCategory>
 */
class QueueCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('?')),
            'is_active' => true,
        ];
    }
}
