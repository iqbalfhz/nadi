<?php

namespace Database\Factories;

use App\Models\ObArea;
use App\Models\ObChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObChecklist>
 */
class ObChecklistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ob_area_id' => ObArea::factory(),
            'user_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
