<?php

namespace Database\Factories;

use App\Models\Bazaar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bazaar>
 */
class BazaarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'is_open' => true,
        ];
    }
}
