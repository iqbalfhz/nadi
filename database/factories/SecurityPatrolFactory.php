<?php

namespace Database\Factories;

use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityPatrol>
 */
class SecurityPatrolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'security_checkpoint_id' => SecurityCheckpoint::factory(),
            'user_id' => User::factory(),
            'incident_report' => fake()->optional()->sentence(),
        ];
    }
}
