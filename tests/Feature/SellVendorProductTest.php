<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use App\Filament\App\Pages\SellVendorProduct;
use App\Models\Bazaar;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SellVendorProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_a_cashier_can_sell_a_per_piece_product(): void
    {
        $cashier = $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 8000,
        ]);

        Livewire::test(SellVendorProduct::class)
            ->set('bazaarId', $bazaar->id)
            ->set('vendorId', $vendor->id)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 3)
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell');

        $sale = VendorSale::query()->where('vendor_product_id', $product->id)->firstOrFail();

        $this->assertSame(24000, $sale->price);
        $this->assertSame($cashier->id, $sale->sold_by);
    }

    public function test_a_cashier_can_sell_a_per_100g_product(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerHundredGrams,
            'price' => 45000,
        ]);

        Livewire::test(SellVendorProduct::class)
            ->set('bazaarId', $bazaar->id)
            ->set('vendorId', $vendor->id)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 250)
            ->set('paymentMethod', TicketPaymentMethod::Qris->value)
            ->call('sell');

        $sale = VendorSale::query()->where('vendor_product_id', $product->id)->firstOrFail();

        $this->assertSame(112500, $sale->price);
        $this->assertSame(250, $sale->quantity);
    }

    public function test_only_open_bazaars_appear_in_the_dropdown(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $open = Bazaar::factory()->create(['is_open' => true]);
        $closed = Bazaar::factory()->create(['is_open' => false]);

        $component = Livewire::test(SellVendorProduct::class);

        $this->assertTrue($component->get('openBazaars')->contains('id', $open->id));
        $this->assertFalse($component->get('openBazaars')->contains('id', $closed->id));
    }

    public function test_vendor_options_narrow_to_the_selected_bazaar(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $bazaarA = Bazaar::factory()->create(['is_open' => true]);
        $bazaarB = Bazaar::factory()->create(['is_open' => true]);
        $vendorA = Vendor::factory()->create(['bazaar_id' => $bazaarA->id]);
        $vendorB = Vendor::factory()->create(['bazaar_id' => $bazaarB->id]);

        $component = Livewire::test(SellVendorProduct::class)->set('bazaarId', $bazaarA->id);

        $this->assertTrue($component->get('vendorsForSelectedBazaar')->contains('id', $vendorA->id));
        $this->assertFalse($component->get('vendorsForSelectedBazaar')->contains('id', $vendorB->id));
    }

    public function test_product_options_narrow_to_the_selected_vendor(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $vendorA = Vendor::factory()->create();
        $vendorB = Vendor::factory()->create();
        $productA = VendorProduct::factory()->create(['vendor_id' => $vendorA->id]);
        $productB = VendorProduct::factory()->create(['vendor_id' => $vendorB->id]);

        $component = Livewire::test(SellVendorProduct::class)->set('vendorId', $vendorA->id);

        $this->assertTrue($component->get('productsForSelectedVendor')->contains('id', $productA->id));
        $this->assertFalse($component->get('productsForSelectedVendor')->contains('id', $productB->id));
    }

    public function test_selecting_a_new_bazaar_resets_the_vendor_and_product_selection(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $vendor = Vendor::factory()->create();
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $anotherBazaar = Bazaar::factory()->create();

        Livewire::test(SellVendorProduct::class)
            ->set('vendorId', $vendor->id)
            ->set('vendorProductId', $product->id)
            ->set('bazaarId', $anotherBazaar->id)
            ->assertSet('vendorId', null)
            ->assertSet('vendorProductId', null);
    }

    public function test_the_selected_products_pricing_unit_drives_the_quantity_field_label(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create(['pricing_unit' => PricingUnit::PerHundredGrams]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorId', $product->vendor_id)
            ->set('vendorProductId', $product->id);

        $this->assertSame('Berat (gram)', $component->get('selectedProduct')->pricing_unit->quantityFieldLabel());
    }

    public function test_selling_without_selecting_a_product_does_not_create_a_sale(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        Livewire::test(SellVendorProduct::class)
            ->set('quantity', 1)
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell');

        $this->assertDatabaseCount('vendor_sales', 0);
    }

    public function test_selling_zero_quantity_does_not_create_a_sale(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create();

        Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 0)
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell');

        $this->assertDatabaseCount('vendor_sales', 0);
    }

    public function test_next_sale_clears_the_receipt(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create();

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 1)
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('sell')
            ->assertSet('lastSaleId', fn (?int $id) => $id !== null);

        $component->call('nextSale')->assertSet('lastSaleId', null);
    }

    public function test_a_cashier_without_the_permission_is_forbidden_from_the_sell_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(SellVendorProduct::getUrl())
            ->assertForbidden();
    }

    public function test_a_cashier_with_the_permission_can_access_the_sell_page(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $this->get(SellVendorProduct::getUrl())->assertOk();
    }
}
