<?php

namespace Database\Factories;

use App\Enums\MessengerDeliveryStatus;
use App\Models\MessengerDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MessengerDelivery>
 */
class MessengerDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_number' => 'MSG-'.strtoupper(Str::random(8)),
            'sender_id' => User::factory(),
            'destination' => fake()->company(),
            'document_description' => fake()->sentence(4),
            'status' => MessengerDeliveryStatus::Available,
        ];
    }
}
