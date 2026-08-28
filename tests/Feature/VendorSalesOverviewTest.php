<?php

namespace Tests\Feature;

use App\Enums\TicketPaymentMethod;
use App\Filament\Widgets\VendorSalesOverview;
use App\Models\Bazaar;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class VendorSalesOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_the_open_bazaars_sales(): void
    {
        $openBazaar = Bazaar::factory()->create(['is_open' => true]);
        $closedBazaar = Bazaar::factory()->create(['is_open' => false]);

        // vendor_id/vendor_product_id pinned to their intended bazaar — left
        // at factory defaults, each call would spawn its own incidental
        // Vendor/Bazaar chain (Bazaar's default is_open: true too), any of
        // which could win the widget's own "latest open bazaar" query and
        // make this assertion flaky.
        $vendorInOpen = Vendor::factory()->create(['bazaar_id' => $openBazaar->id]);
        $productInOpen = VendorProduct::factory()->create(['vendor_id' => $vendorInOpen->id]);
        $vendorInClosed = Vendor::factory()->create(['bazaar_id' => $closedBazaar->id]);
        $productInClosed = VendorProduct::factory()->create(['vendor_id' => $vendorInClosed->id]);

        VendorSale::factory()->create([
            'bazaar_id' => $openBazaar->id,
            'vendor_id' => $vendorInOpen->id,
            'vendor_product_id' => $productInOpen->id,
            'price' => 15000,
            'payment_method' => TicketPaymentMethod::Cash,
        ]);

        VendorSale::factory()->create([
            'bazaar_id' => $openBazaar->id,
            'vendor_id' => $vendorInOpen->id,
            'vendor_product_id' => $productInOpen->id,
            'price' => 25000,
            'payment_method' => TicketPaymentMethod::Qris,
        ]);

        // Belongs to a different (closed) bazaar — must not be counted.
        VendorSale::factory()->create([
            'bazaar_id' => $closedBazaar->id,
            'vendor_id' => $vendorInClosed->id,
            'vendor_product_id' => $productInClosed->id,
            'price' => 25000,
        ]);

        $stats = (new ReflectionMethod(VendorSalesOverview::class, 'getStats'))
            ->invoke(new VendorSalesOverview);

        [$total, $revenue, $tax, $cash, $qris, $edc] = $stats;

        $this->assertSame('2', $total->getValue());
        $this->assertSame('Rp 40.000', $revenue->getValue());
        $this->assertSame('1', $cash->getValue());
        $this->assertSame('1', $qris->getValue());
        $this->assertSame('0', $edc->getValue());
    }

    public function test_it_does_not_crash_when_no_bazaar_exists_at_all(): void
    {
        $stats = (new ReflectionMethod(VendorSalesOverview::class, 'getStats'))
            ->invoke(new VendorSalesOverview);

        [$total] = $stats;

        $this->assertSame('0', $total->getValue());
    }

    public function test_it_falls_back_to_the_latest_bazaar_when_none_is_open(): void
    {
        $older = Bazaar::factory()->create(['is_open' => false, 'created_at' => now()->subDay()]);
        $latest = Bazaar::factory()->create(['is_open' => false]);

        // vendor_id/vendor_product_id must be pinned to the same bazaar too —
        // leaving them at their factory defaults would spawn extra unrelated
        // Bazaar rows (via Vendor/VendorProduct's own nested factories),
        // which can race $latest for "most recently created" and break this
        // assertion.
        $vendorInOlder = Vendor::factory()->create(['bazaar_id' => $older->id]);
        $productInOlder = VendorProduct::factory()->create(['vendor_id' => $vendorInOlder->id]);
        $vendorInLatest = Vendor::factory()->create(['bazaar_id' => $latest->id]);
        $productInLatest = VendorProduct::factory()->create(['vendor_id' => $vendorInLatest->id]);

        VendorSale::factory()->create([
            'bazaar_id' => $older->id,
            'vendor_id' => $vendorInOlder->id,
            'vendor_product_id' => $productInOlder->id,
            'price' => 25000,
        ]);
        VendorSale::factory()->create([
            'bazaar_id' => $latest->id,
            'vendor_id' => $vendorInLatest->id,
            'vendor_product_id' => $productInLatest->id,
            'price' => 15000,
        ]);

        $stats = (new ReflectionMethod(VendorSalesOverview::class, 'getStats'))
            ->invoke(new VendorSalesOverview);

        [$total] = $stats;

        $this->assertSame('1', $total->getValue());
    }

    public function test_multiple_line_items_sharing_one_transaction_number_count_as_one_transaction(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $product->id,
            'price' => 15000,
            'transaction_number' => 'BZR-SHARED',
        ]);
        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $product->id,
            'price' => 25000,
            'transaction_number' => 'BZR-SHARED',
        ]);

        $stats = (new ReflectionMethod(VendorSalesOverview::class, 'getStats'))
            ->invoke(new VendorSalesOverview);

        [$total, $revenue] = $stats;

        $this->assertSame('1', $total->getValue());
        $this->assertSame('Rp 40.000', $revenue->getValue());
    }

    public function test_scope_to_today_excludes_sales_from_other_days(): void
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

        $widget = new VendorSalesOverview;
        $widget->scopeToToday = true;

        $stats = (new ReflectionMethod(VendorSalesOverview::class, 'getStats'))->invoke($widget);

        [$total, $revenue] = $stats;

        $this->assertSame('1', $total->getValue());
        $this->assertSame('Rp 15.000', $revenue->getValue());
    }
}
