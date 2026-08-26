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
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 8000,
        ]);
        $cashier = User::factory()->create();

        $sales = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 3]], $cashier, TicketPaymentMethod::Cash);

        $this->assertSame(24000, $sales->first()->price);
    }

    public function test_per_100g_pricing_computes_proportional_price_rounded_to_the_nearest_rupiah(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerHundredGrams,
            'price' => 33333,
        ]);
        $cashier = User::factory()->create();

        $sales = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 150]], $cashier, TicketPaymentMethod::Cash);

        // 33333 * 150 / 100 = 49999.5 -> rounds half-up to 50000, not truncated.
        $this->assertSame(50000, $sales->first()->price);
    }

    public function test_per_100g_pricing_is_not_bucketed_to_the_nearest_100g(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerHundredGrams,
            'price' => 45000,
        ]);
        $cashier = User::factory()->create();

        $sales = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 333]], $cashier, TicketPaymentMethod::Cash);

        // Exact proportional value from 333/100, not bucketed to 300 or 400 first.
        $this->assertSame((int) round(333 * 45000 / 100), $sales->first()->price);
        $this->assertNotSame((int) round(300 * 45000 / 100), $sales->first()->price);
    }

    public function test_price_is_snapshotted_and_unaffected_by_later_product_price_changes(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 5000,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 2]], $cashier, TicketPaymentMethod::Cash)->first();

        $product->update(['price' => 99999]);

        $this->assertSame(10000, $sale->fresh()->price);
    }

    public function test_pricing_unit_is_snapshotted_onto_the_sale_row_and_unaffected_by_later_product_changes(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerHundredGrams,
        ]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 100]], $cashier, TicketPaymentMethod::Cash)->first();

        $product->update(['pricing_unit' => PricingUnit::PerPiece]);

        $this->assertSame(PricingUnit::PerHundredGrams, $sale->fresh()->pricing_unit);
    }

    public function test_bazaar_id_and_vendor_id_are_derived_from_the_locked_product_not_from_caller_input(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 1]], $cashier, TicketPaymentMethod::Cash)->first();

        $this->assertSame($bazaar->id, $sale->bazaar_id);
        $this->assertSame($vendor->id, $sale->vendor_id);
    }

    public function test_a_transaction_number_is_auto_generated_and_unique_per_checkout(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        $one = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 1]], $cashier, TicketPaymentMethod::Cash)->first();
        $two = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 1]], $cashier, TicketPaymentMethod::Cash)->first();

        $this->assertNotEmpty($one->transaction_number);
        $this->assertNotEmpty($two->transaction_number);
        $this->assertNotSame($one->transaction_number, $two->transaction_number);
    }

    public function test_every_item_in_one_checkout_shares_the_same_transaction_number(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $productA = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $productB = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        $sales = VendorSale::sellCartFor($bazaar, [
            ['product' => $productA, 'quantity' => 1],
            ['product' => $productB, 'quantity' => 2],
        ], $cashier, TicketPaymentMethod::Cash);

        $this->assertCount(2, $sales);
        $this->assertSame($sales[0]->transaction_number, $sales[1]->transaction_number);
    }

    public function test_a_cart_can_span_multiple_vendors_in_the_same_bazaar(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendorA = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $vendorB = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $productA = VendorProduct::factory()->create(['vendor_id' => $vendorA->id]);
        $productB = VendorProduct::factory()->create(['vendor_id' => $vendorB->id]);
        $cashier = User::factory()->create();

        $sales = VendorSale::sellCartFor($bazaar, [
            ['product' => $productA, 'quantity' => 1],
            ['product' => $productB, 'quantity' => 1],
        ], $cashier, TicketPaymentMethod::Cash);

        $this->assertSame($vendorA->id, $sales->firstWhere('vendor_product_id', $productA->id)->vendor_id);
        $this->assertSame($vendorB->id, $sales->firstWhere('vendor_product_id', $productB->id)->vendor_id);
    }

    public function test_checking_out_when_the_bazaar_is_closed_fails_and_creates_no_sales(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => false]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        $this->expectException(RuntimeException::class);

        try {
            VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 1]], $cashier, TicketPaymentMethod::Cash);
        } finally {
            $this->assertDatabaseCount('vendor_sales', 0);
        }
    }

    public function test_checking_out_a_multi_item_cart_is_all_or_nothing_when_the_bazaar_is_closed(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => false]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $productA = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $productB = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $cashier = User::factory()->create();

        try {
            VendorSale::sellCartFor($bazaar, [
                ['product' => $productA, 'quantity' => 1],
                ['product' => $productB, 'quantity' => 1],
            ], $cashier, TicketPaymentMethod::Cash);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('vendor_sales', 0);
    }

    public function test_selling_within_stock_succeeds(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id, 'initial_stock' => 10]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 10]], $cashier, TicketPaymentMethod::Cash)->first();

        $this->assertNotNull($sale->id);
    }

    public function test_selling_more_than_the_remaining_stock_fails_and_creates_no_sale(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id, 'initial_stock' => 5]);
        $cashier = User::factory()->create();

        $this->expectException(RuntimeException::class);

        try {
            VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 6]], $cashier, TicketPaymentMethod::Cash);
        } finally {
            $this->assertDatabaseCount('vendor_sales', 0);
        }
    }

    public function test_a_null_initial_stock_means_unlimited(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id, 'initial_stock' => null]);
        $cashier = User::factory()->create();

        $sale = VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 999999]], $cashier, TicketPaymentMethod::Cash)->first();

        $this->assertNotNull($sale->id);
    }

    public function test_stock_check_accounts_for_previously_sold_quantity(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id, 'initial_stock' => 10]);
        $cashier = User::factory()->create();

        VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 7]], $cashier, TicketPaymentMethod::Cash);

        $this->expectException(RuntimeException::class);

        VendorSale::sellCartFor($bazaar, [['product' => $product, 'quantity' => 4]], $cashier, TicketPaymentMethod::Cash);
    }

    public function test_stock_check_accounts_for_earlier_items_of_the_same_product_within_one_cart(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id, 'initial_stock' => 10]);
        $cashier = User::factory()->create();

        $this->expectException(RuntimeException::class);

        try {
            VendorSale::sellCartFor($bazaar, [
                ['product' => $product, 'quantity' => 6],
                ['product' => $product, 'quantity' => 6],
            ], $cashier, TicketPaymentMethod::Cash);
        } finally {
            // All-or-nothing — the first line item's stock-check-passing
            // write must be rolled back too, not left dangling.
            $this->assertDatabaseCount('vendor_sales', 0);
        }
    }
}
