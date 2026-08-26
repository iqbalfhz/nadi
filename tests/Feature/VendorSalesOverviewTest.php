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

        VendorSale::factory()->create([
            'bazaar_id' => $openBazaar->id,
            'price' => 15000,
            'payment_method' => TicketPaymentMethod::Cash,
        ]);

        VendorSale::factory()->create([
            'bazaar_id' => $openBazaar->id,
            'price' => 25000,
            'payment_method' => TicketPaymentMethod::Qris,
        ]);

        // Belongs to a different (closed) bazaar — must not be counted.
        VendorSale::factory()->create([
            'bazaar_id' => $closedBazaar->id,
            'price' => 25000,
        ]);

        $stats = (new ReflectionMethod(VendorSalesOverview::class, 'getStats'))
            ->invoke(new VendorSalesOverview);

        [$total, $revenue, $cash, $qris, $edc] = $stats;

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

    public function test_scope_to_today_excludes_sales_from_other_days(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);

        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'price' => 20000,
            'created_at' => now()->subDay(),
        ]);

        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
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
