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

    public function test_a_cashier_can_checkout_a_single_item_cart(): void
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
            ->call('addToCart')
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('checkout');

        $sale = VendorSale::query()->where('vendor_product_id', $product->id)->firstOrFail();

        $this->assertSame(24000, $sale->price);
        $this->assertSame($cashier->id, $sale->sold_by);
    }

    public function test_a_cashier_can_checkout_a_cart_with_multiple_different_products(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $durian = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerHundredGrams,
            'price' => 45000,
        ]);
        $esDuren = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 10000,
        ]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('bazaarId', $bazaar->id)
            ->set('vendorId', $vendor->id)
            ->set('vendorProductId', $durian->id)
            ->set('quantity', 250)
            ->call('addToCart')
            ->set('vendorProductId', $esDuren->id)
            ->set('quantity', 2)
            ->call('addToCart');

        $this->assertCount(2, $component->get('cart'));

        $component
            ->set('paymentMethod', TicketPaymentMethod::Qris->value)
            ->call('checkout');

        $durianSale = VendorSale::query()->where('vendor_product_id', $durian->id)->firstOrFail();
        $esDurenSale = VendorSale::query()->where('vendor_product_id', $esDuren->id)->firstOrFail();

        $this->assertSame(112500, $durianSale->price);
        $this->assertSame(20000, $esDurenSale->price);
        $this->assertSame($durianSale->transaction_number, $esDurenSale->transaction_number);
    }

    public function test_adding_to_cart_keeps_the_bazaar_and_vendor_selected_for_the_next_item(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        Livewire::test(SellVendorProduct::class)
            ->set('bazaarId', $bazaar->id)
            ->set('vendorId', $vendor->id)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 1)
            ->call('addToCart')
            ->assertSet('bazaarId', $bazaar->id)
            ->assertSet('vendorId', $vendor->id)
            ->assertSet('vendorProductId', null)
            ->assertSet('quantity', null);
    }

    public function test_removing_an_item_from_the_cart(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $vendor = Vendor::factory()->create();
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 1)
            ->call('addToCart');

        $this->assertCount(1, $component->get('cart'));

        $component->call('removeFromCart', 0);

        $this->assertCount(0, $component->get('cart'));
    }

    public function test_switching_bazaars_clears_the_cart(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $bazaarA = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaarA->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        $bazaarB = Bazaar::factory()->create(['is_open' => true]);

        Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 1)
            ->call('addToCart')
            ->set('bazaarId', $bazaarB->id)
            ->assertSet('cart', []);
    }

    public function test_adding_more_than_the_remaining_stock_to_the_cart_is_rejected(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create(['initial_stock' => 5]);

        Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 6)
            ->call('addToCart')
            ->assertSet('cart', []);
    }

    public function test_adding_within_stock_to_the_cart_succeeds(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create(['initial_stock' => 5]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 5)
            ->call('addToCart');

        $this->assertCount(1, $component->get('cart'));
    }

    public function test_a_product_with_unlimited_stock_can_always_be_added(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create(['initial_stock' => null]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 999999)
            ->call('addToCart');

        $this->assertCount(1, $component->get('cart'));
    }

    public function test_adding_the_same_product_twice_respects_the_combined_stock_limit(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create(['initial_stock' => 10]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 6)
            ->call('addToCart')
            ->set('vendorProductId', $product->id)
            ->set('quantity', 6)
            ->call('addToCart');

        // 6 + 6 = 12 exceeds the stock of 10 — the second add must be
        // rejected, leaving only the first item in the cart.
        $this->assertCount(1, $component->get('cart'));
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

    public function test_the_selected_products_pricing_unit_drives_the_quantity_field_label(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create(['pricing_unit' => PricingUnit::PerHundredGrams]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('vendorId', $product->vendor_id)
            ->set('vendorProductId', $product->id);

        $this->assertSame('Berat (gram)', $component->get('selectedProduct')->pricing_unit->quantityFieldLabel());
    }

    public function test_adding_to_cart_without_selecting_a_product_does_not_add_anything(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        Livewire::test(SellVendorProduct::class)
            ->set('quantity', 1)
            ->call('addToCart')
            ->assertSet('cart', []);
    }

    public function test_adding_zero_quantity_to_cart_does_not_add_anything(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $product = VendorProduct::factory()->create();

        Livewire::test(SellVendorProduct::class)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 0)
            ->call('addToCart')
            ->assertSet('cart', []);
    }

    public function test_checking_out_an_empty_cart_does_not_create_a_sale(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        Livewire::test(SellVendorProduct::class)
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('checkout');

        $this->assertDatabaseCount('vendor_sales', 0);
    }

    public function test_next_sale_clears_the_receipt(): void
    {
        $this->actingAsEmployeeWithPermissions('View:SellVendorProduct');

        $vendor = Vendor::factory()->create();
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);

        $component = Livewire::test(SellVendorProduct::class)
            ->set('bazaarId', $vendor->bazaar_id)
            ->set('vendorId', $vendor->id)
            ->set('vendorProductId', $product->id)
            ->set('quantity', 1)
            ->call('addToCart')
            ->set('paymentMethod', TicketPaymentMethod::Cash->value)
            ->call('checkout')
            ->assertSet('lastTransactionNumber', fn (?string $number) => $number !== null);

        $component->call('nextSale')->assertSet('lastTransactionNumber', null);
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
