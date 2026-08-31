<?php

namespace Database\Factories;

use App\Models\HkCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HkCategory>
 */
class HkCategoryFactory extends Factory
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
            'requires_floor' => false,
            'is_active' => true,
        ];
    }

    /**
     * A category that collects the floor separately, the way Public Area does.
     */
    public function requiringFloor(): static
    {
        return $this->state(fn (): array => ['requires_floor' => true]);
    }
}
