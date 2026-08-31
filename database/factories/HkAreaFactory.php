<?php

namespace Database\Factories;

use App\Models\HkArea;
use App\Models\HkCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HkArea>
 */
class HkAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hk_category_id' => HkCategory::factory(),
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }
}
