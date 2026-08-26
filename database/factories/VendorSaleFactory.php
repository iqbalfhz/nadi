<?php

namespace Database\Factories;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use App\Models\Bazaar;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorSale>
 */
class VendorSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bazaar_id' => Bazaar::factory(),
            'vendor_id' => Vendor::factory(),
            'vendor_product_id' => VendorProduct::factory(),
            'quantity' => 1,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 5000,
            'payment_method' => fake()->randomElement(TicketPaymentMethod::cases()),
            'sold_by' => User::factory(),
        ];
    }
}
