<?php

namespace Database\Factories;

use App\Models\SecurityCheckpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SecurityCheckpoint>
 */
class SecurityCheckpointFactory extends Factory
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
            'code' => Str::random(32),
            'is_active' => true,
        ];
    }
}
