<?php

namespace Database\Factories;

use App\Enums\BarcodeFormat;
use App\Models\Barcode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barcode>
 */
class BarcodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'format' => BarcodeFormat::Qr,
            'content' => fake()->url(),
            'label' => null,
            'created_by' => User::factory(),
        ];
    }
}
