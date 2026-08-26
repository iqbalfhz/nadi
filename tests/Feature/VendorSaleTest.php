<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use App\Models\Bazaar;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class VendorSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_per_piece_pricing_multiplies_quantity_by_unit_price(): void
    {
        $product = VendorProduct::factory()->create([
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 8000,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellFor($product, $cashier, 3, TicketPaymentMethod::Cash);

        $this->assertSame(24000, $sale->price);
    }

    public function test_per_100g_pricing_computes_proportional_price_rounded_to_the_nearest_rupiah(): void
    {
        $product = VendorProduct::factory()->create([
            'pricing_unit' => PricingUnit::PerHundredGrams,
            'price' => 33333,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellFor($product, $cashier, 150, TicketPaymentMethod::Cash);

        // 33333 * 150 / 100 = 49999.5 -> rounds half-up to 50000, not truncated.
        $this->assertSame(50000, $sale->price);
    }

    public function test_per_100g_pricing_is_not_bucketed_to_the_nearest_100g(): void
    {
        $product = VendorProduct::factory()->create([
            'pricing_unit' => PricingUnit::PerHundredGrams,
            'price' => 45000,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellFor($product, $cashier, 333, TicketPaymentMethod::Cash);

        // Exact proportional value from 333/100, not bucketed to 300 or 400 first.
        $this->assertSame((int) round(333 * 45000 / 100), $sale->price);
        $this->assertNotSame((int) round(300 * 45000 / 100), $sale->price);
    }

    public function test_price_is_snapshotted_and_unaffected_by_later_product_price_changes(): void
    {
        $product = VendorProduct::factory()->create([
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 5000,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellFor($product, $cashier, 2, TicketPaymentMethod::Cash);

        $product->update(['price' => 99999]);

        $this->assertSame(10000, $sale->fresh()->price);
    }

    public function test_pricing_unit_is_snapshotted_onto_the_sale_row_and_unaffected_by_later_product_changes(): void
    {
        $product = VendorProduct::factory()->create([
            'pricing_unit' => PricingUnit::PerHundredGrams,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellFor($product, $cashier, 100, TicketPaymentMethod::Cash);

        $product->update(['pricing_unit' => PricingUnit::PerPiece]);

        $this->assertSame(PricingUnit::PerHundredGrams, $sale->fresh()->pricing_unit);
    }

    public function test_bazaar_id_and_vendor_id_are_derived_from_the_locked_product_not_from_caller_input(): void
    {
        $bazaar = Bazaar::factory()->create();
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellFor($product, $cashier, 1, TicketPaymentMethod::Cash);

        $this->assertSame($bazaar->id, $sale->bazaar_id);
        $this->assertSame($vendor->id, $sale->vendor_id);
    }

    public function test_a_transaction_number_is_auto_generated_and_unique(): void
    {
        $product = VendorProduct::factory()->create();
        $cashier = User::factory()->create();

        $one = VendorSale::sellFor($product, $cashier, 1, TicketPaymentMethod::Cash);
        $two = VendorSale::sellFor($product, $cashier, 1, TicketPaymentMethod::Cash);

        $this->assertNotEmpty($one->transaction_number);
        $this->assertNotEmpty($two->transaction_number);
        $this->assertNotSame($one->transaction_number, $two->transaction_number);
    }

    public function test_selling_when_the_bazaar_is_closed_fails_and_creates_no_sale(): void
    {
        $vendor = Vendor::factory()->for(Bazaar::factory()->state(['is_open' => false]))->create();
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        $this->expectException(RuntimeException::class);

        try {
            VendorSale::sellFor($product, $cashier, 1, TicketPaymentMethod::Cash);
        } finally {
            $this->assertDatabaseCount('vendor_sales', 0);
        }
    }
}
