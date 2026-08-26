<?php

namespace Database\Factories;

use App\Enums\PricingUnit;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorProduct>
 */
class VendorProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->unique()->word(),
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 5000,
        ];
    }
}
