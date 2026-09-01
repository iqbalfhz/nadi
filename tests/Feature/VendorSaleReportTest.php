<?php

namespace Tests\Feature;

use App\Filament\App\Resources\VendorSales\Pages\ListVendorSales as AppListVendorSales;
use App\Filament\Resources\VendorSales\Pages\ListVendorSales as AdminListVendorSales;
use App\Models\Bazaar;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSaleReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A sale whose vendor and product really do belong to the given bazaar.
     *
     * VendorSale::factory() on its own spawns one Bazaar for the vendor and
     * another for the product, so the sale ends up filed under one bazaar
     * while its vendor belongs to a different one — a shape
     * VendorSale::sellFor() can never produce, since it derives both from the
     * locked product. It also leaves a *newer* bazaar behind, which the /app
     * report's "latest bazaar" default filter then selects instead of the one
     * the test is about.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function saleAt(Bazaar $bazaar, array $attributes = [], ?Vendor $vendor = null): VendorSale
    {
        $vendor ??= Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        return VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $product->id,
            ...$attributes,
        ]);
    }

    public function test_an_employee_with_the_permission_can_view_the_app_report(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');

        $sale = $this->saleAt(Bazaar::factory()->create());

        Livewire::test(AppListVendorSales::class)
            ->assertCanSeeTableRecords([$sale]);
    }

    public function test_the_app_report_shows_sales_from_every_cashier_not_just_the_current_user(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');
        $bazaar = Bazaar::factory()->create();

        $mine = $this->saleAt($bazaar, ['sold_by' => $me->id]);
        $theirs = $this->saleAt($bazaar, ['sold_by' => $someoneElse->id]);

        Livewire::test(AppListVendorSales::class)
            ->assertCanSeeTableRecords([$mine, $theirs]);
    }

    public function test_the_app_report_can_be_filtered_by_vendor_for_settlement(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');

        $bazaar = Bazaar::factory()->create();
        $vendorA = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $vendorB = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);

        $saleA = $this->saleAt($bazaar, vendor: $vendorA);
        $saleB = $this->saleAt($bazaar, vendor: $vendorB);

        Livewire::test(AppListVendorSales::class)
            ->filterTable('vendor_id', $vendorA->id)
            ->assertCanSeeTableRecords([$saleA])
            ->assertCanNotSeeTableRecords([$saleB]);
    }

    public function test_the_app_report_can_be_filtered_by_bazaar(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAsEmployeeWithPermissions('ViewAny:VendorSale');

        $bazaarA = Bazaar::factory()->create();
        $bazaarB = Bazaar::factory()->create();

        $saleA = $this->saleAt($bazaarA);
        $saleB = $this->saleAt($bazaarB);

        Livewire::test(AppListVendorSales::class)
            ->filterTable('bazaar_id', $bazaarA->id)
            ->assertCanSeeTableRecords([$saleA])
            ->assertCanNotSeeTableRecords([$saleB]);
    }

    public function test_the_admin_report_shows_sales_from_every_cashier_too(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAsSuperAdmin();

        $saleA = VendorSale::factory()->create();
        $saleB = VendorSale::factory()->create(['sold_by' => User::factory()->create()->id]);

        Livewire::test(AdminListVendorSales::class)
            ->assertCanSeeTableRecords([$saleA, $saleB]);
    }
}
