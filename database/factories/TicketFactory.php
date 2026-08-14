<?php

namespace Database\Factories;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'buyer_name' => fake()->name(),
            'is_member' => fake()->boolean(),
            'member_reference' => null,
            'payment_method' => fake()->randomElement(TicketPaymentMethod::cases()),
            'price' => 25000,
            'sold_by' => User::factory(),
        ];
    }
}
