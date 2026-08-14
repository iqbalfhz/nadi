<?php

namespace Database\Factories;

use App\Models\ObArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObArea>
 */
class ObAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }
}
