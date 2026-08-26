<?php

namespace Tests\Feature;

use App\Filament\Widgets\VendorSettlementOverview;
use App\Models\Bazaar;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSettlementOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_one_row_per_vendor_with_that_vendors_revenue_and_count(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        VendorSale::factory()->create(['bazaar_id' => $bazaar->id, 'vendor_id' => $vendor->id, 'vendor_product_id' => $product->id, 'price' => 15000]);
        VendorSale::factory()->create(['bazaar_id' => $bazaar->id, 'vendor_id' => $vendor->id, 'vendor_product_id' => $product->id, 'price' => 25000]);

        Livewire::test(VendorSettlementOverview::class)
            ->assertCanSeeTableRecords([$vendor])
            ->assertTableColumnStateSet('sales_count', 2, $vendor)
            ->assertTableColumnStateSet('sales_revenue', 40000, $vendor);
    }

    public function test_revenue_is_isolated_per_vendor_and_does_not_leak_between_vendors(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendorA = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $vendorB = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $productA = VendorProduct::factory()->create(['vendor_id' => $vendorA->id]);
        $productB = VendorProduct::factory()->create(['vendor_id' => $vendorB->id]);

        VendorSale::factory()->create(['bazaar_id' => $bazaar->id, 'vendor_id' => $vendorA->id, 'vendor_product_id' => $productA->id, 'price' => 10000]);
        VendorSale::factory()->create(['bazaar_id' => $bazaar->id, 'vendor_id' => $vendorA->id, 'vendor_product_id' => $productA->id, 'price' => 10000]);
        VendorSale::factory()->create(['bazaar_id' => $bazaar->id, 'vendor_id' => $vendorB->id, 'vendor_product_id' => $productB->id, 'price' => 99000]);

        Livewire::test(VendorSettlementOverview::class)
            ->assertTableColumnStateSet('sales_revenue', 20000, $vendorA)
            ->assertTableColumnStateSet('sales_revenue', 99000, $vendorB);
    }

    public function test_it_falls_back_to_the_latest_bazaar_when_none_is_open(): void
    {
        $older = Bazaar::factory()->create(['is_open' => false, 'created_at' => now()->subDay()]);
        $latest = Bazaar::factory()->create(['is_open' => false]);

        $vendorInOlder = Vendor::factory()->create(['bazaar_id' => $older->id]);
        $vendorInLatest = Vendor::factory()->create(['bazaar_id' => $latest->id]);

        Livewire::test(VendorSettlementOverview::class)
            ->assertCanSeeTableRecords([$vendorInLatest])
            ->assertCanNotSeeTableRecords([$vendorInOlder]);
    }

    public function test_scope_to_today_excludes_other_days_from_the_per_vendor_totals(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $product->id,
            'price' => 20000,
            'created_at' => now()->subDay(),
        ]);

        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $product->id,
            'price' => 15000,
        ]);

        Livewire::test(VendorSettlementOverview::class, ['scopeToToday' => true])
            ->assertTableColumnStateSet('sales_count', 1, $vendor)
            ->assertTableColumnStateSet('sales_revenue', 15000, $vendor);
    }
}
