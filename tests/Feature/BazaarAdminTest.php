<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use App\Filament\App\Resources\VendorSales\VendorSaleResource as AppVendorSaleResource;
use App\Filament\Resources\Bazaars\BazaarResource;
use App\Filament\Resources\Bazaars\Pages\CreateBazaar;
use App\Filament\Resources\Bazaars\Pages\EditBazaar;
use App\Filament\Resources\VendorSales\Pages\EditVendorSale;
use App\Filament\Resources\VendorSales\VendorSaleResource;
use App\Models\Bazaar;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BazaarAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_admin_can_create_a_bazaar_with_vendors_and_products_in_one_form(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateBazaar::class)
            ->fillForm([
                'name' => 'Bazar Durian Agustus',
                'is_open' => false,
                'vendors' => [
                    [
                        'name' => 'Kios A',
                        'tax_rate' => 10,
                        'products' => [
                            ['name' => 'Duren Montong', 'pricing_unit' => PricingUnit::PerHundredGrams->value, 'price' => 45000, 'initial_stock' => 5000],
                            ['name' => 'Es Duren', 'pricing_unit' => PricingUnit::PerPiece->value, 'price' => 10000],
                        ],
                    ],
                    [
                        // Not every stall is liable — this one sits below
                        // the threshold, which is why the rate is per kios.
                        'name' => 'Kios B',
                        'tax_rate' => 0,
                        'products' => [
                            ['name' => 'Air Mineral', 'pricing_unit' => PricingUnit::PerPiece->value, 'price' => 5000],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $bazaar = Bazaar::query()->where('name', 'Bazar Durian Agustus')->firstOrFail();

        $this->assertDatabaseHas('vendors', ['bazaar_id' => $bazaar->id, 'name' => 'Kios A']);
        $this->assertDatabaseHas('vendors', ['bazaar_id' => $bazaar->id, 'name' => 'Kios B']);

        $kiosA = Vendor::query()->where('bazaar_id', $bazaar->id)->where('name', 'Kios A')->firstOrFail();

        $this->assertDatabaseHas('vendor_products', [
            'vendor_id' => $kiosA->id,
            'name' => 'Duren Montong',
            'pricing_unit' => PricingUnit::PerHundredGrams->value,
            'price' => 45000,
            'initial_stock' => 5000,
        ]);
        $this->assertDatabaseHas('vendor_products', [
            'vendor_id' => $kiosA->id,
            'name' => 'Es Duren',
            'initial_stock' => null,
            'pricing_unit' => PricingUnit::PerPiece->value,
            'price' => 10000,
        ]);
    }

    public function test_an_admin_can_open_and_close_a_bazaar(): void
    {
        $this->actingAsSuperAdmin();

        $bazaar = Bazaar::factory()->create(['is_open' => false]);

        Livewire::test(EditBazaar::class, ['record' => $bazaar->getRouteKey()])
            ->fillForm(['is_open' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($bazaar->fresh()->is_open);
    }

    public function test_an_admin_can_add_a_new_vendor_to_an_existing_bazaar_via_edit(): void
    {
        $this->actingAsSuperAdmin();

        $bazaar = Bazaar::factory()->create();
        $existingVendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $existingProduct = VendorProduct::factory()->create(['vendor_id' => $existingVendor->id]);

        // A hand-rolled plain array (keys 0, 1, ...) can't be matched back
        // to the existing relationship-repeater rows, which are keyed by
        // whatever internal key Filament assigned during hydration — the
        // repeater would see the existing item "missing" and delete it. So
        // read the already-hydrated state first and only append the new
        // item to it, leaving the existing item's key untouched.
        $component = Livewire::test(EditBazaar::class, ['record' => $bazaar->getRouteKey()]);

        $vendors = $component->get('data.vendors');
        $vendors[] = [
            'name' => 'Kios Baru',
            'tax_rate' => 10,
            'products' => [
                ['name' => 'Produk Baru', 'pricing_unit' => PricingUnit::PerPiece->value, 'price' => 7000],
            ],
        ];

        $component->fillForm(['vendors' => $vendors])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('vendors', ['id' => $existingVendor->id, 'bazaar_id' => $bazaar->id]);
        $this->assertDatabaseHas('vendor_products', ['id' => $existingProduct->id, 'vendor_id' => $existingVendor->id]);
        $this->assertDatabaseHas('vendors', ['bazaar_id' => $bazaar->id, 'name' => 'Kios Baru']);
    }

    public function test_removing_a_product_with_existing_sales_from_the_repeater_is_blocked(): void
    {
        $this->actingAsSuperAdmin();

        $bazaar = Bazaar::factory()->create();
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id]);
        $product = VendorProduct::factory()->create(['vendor_id' => $vendor->id]);
        VendorSale::factory()->create([
            'bazaar_id' => $bazaar->id,
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $product->id,
        ]);

        $component = Livewire::test(EditBazaar::class, ['record' => $bazaar->getRouteKey()]);

        // Simulate removing the only product row from its vendor, the same
        // way the Repeater's own delete button would — this product still
        // has a sale attached, so saving must be blocked, not raise a raw
        // FK-constraint QueryException.
        $vendors = $component->get('data.vendors');
        $vendorKey = array_key_first($vendors);
        $vendors[$vendorKey]['products'] = [];

        $component->fillForm(['vendors' => $vendors])
            ->call('save')
            ->assertNotified('Tidak bisa menyimpan');

        $this->assertDatabaseHas('vendor_products', ['id' => $product->id]);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);
    }

    public function test_the_vendor_sale_resource_has_no_create_capability(): void
    {
        $this->assertArrayNotHasKey('create', VendorSaleResource::getPages());
    }

    public function test_an_admin_can_correct_a_sales_mistaken_payment_method(): void
    {
        $this->actingAsSuperAdmin();

        $sale = VendorSale::factory()->create(['payment_method' => TicketPaymentMethod::Qris]);

        Livewire::test(EditVendorSale::class, ['record' => $sale->getRouteKey()])
            ->fillForm([
                'payment_method' => TicketPaymentMethod::Cash->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(TicketPaymentMethod::Cash, $sale->fresh()->payment_method);
    }

    public function test_the_app_vendor_sale_resource_has_no_edit_capability(): void
    {
        $this->assertArrayNotHasKey('edit', AppVendorSaleResource::getPages());
    }

    public function test_bazaar_resource_url_is_reachable_by_a_super_admin(): void
    {
        $this->actingAsSuperAdmin();

        $this->get(BazaarResource::getUrl('index'))->assertOk();
    }
}
