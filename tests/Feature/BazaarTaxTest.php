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
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * PB1 is charged on top of the listed price, at a rate that belongs to the
 * kios rather than the bazaar — vendors are independent businesses and not
 * all of them are liable.
 */
class BazaarTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_is_added_on_top_of_the_listed_price(): void
    {
        [$bazaar, $product] = $this->stall(taxRate: 10);

        $sale = $this->sell($bazaar, $product, quantity: 1)->first();

        $this->assertSame(50000, $sale->price, 'price stays the pre-tax subtotal...');
        $this->assertSame(5000, $sale->tax_amount, '...with PB1 recorded beside it,');
        $this->assertSame(55000, $sale->total(), 'and the customer pays both.');
    }

    public function test_a_kios_below_the_threshold_charges_nothing(): void
    {
        [$bazaar, $product] = $this->stall(taxRate: 0);

        $sale = $this->sell($bazaar, $product, quantity: 1)->first();

        $this->assertSame(0, $sale->tax_amount);
        $this->assertSame($sale->price, $sale->total());
    }

    /**
     * The rate lives on the kios precisely so one cart can span a taxed and
     * an untaxed stall at once.
     */
    public function test_one_cart_can_carry_two_different_rates(): void
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);

        $taxed = VendorProduct::factory()->create([
            'vendor_id' => Vendor::factory()->create(['bazaar_id' => $bazaar->id, 'tax_rate' => 10])->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 50000,
        ]);

        $untaxed = VendorProduct::factory()->create([
            'vendor_id' => Vendor::factory()->create(['bazaar_id' => $bazaar->id, 'tax_rate' => 0])->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => 20000,
        ]);

        $sales = VendorSale::sellCartFor(
            $bazaar,
            [
                ['product' => $taxed, 'quantity' => 1],
                ['product' => $untaxed, 'quantity' => 1],
            ],
            User::factory()->create(),
            TicketPaymentMethod::Cash,
        );

        $this->assertSame(70000, $sales->sum('price'));
        $this->assertSame(5000, $sales->sum('tax_amount'), 'Only the taxed stall contributes PB1.');
    }

    /**
     * Snapshotted like price and pricing_unit already are: changing a rate
     * afterwards must never rewrite what a customer was actually charged.
     */
    public function test_changing_the_rate_later_leaves_past_sales_alone(): void
    {
        [$bazaar, $product, $vendor] = $this->stall(taxRate: 10);

        $sale = $this->sell($bazaar, $product, quantity: 1)->first();

        $vendor->update(['tax_rate' => 25]);

        $this->assertSame(5000, $sale->fresh()->tax_amount);
        $this->assertSame('10.00', $sale->fresh()->tax_rate);
    }

    public function test_tax_is_rounded_to_the_whole_rupiah(): void
    {
        [$bazaar, $product] = $this->stall(taxRate: 10, price: 33333);

        // 33.333 x 10% = 3.333,3 -> 3.333
        $this->assertSame(3333, $this->sell($bazaar, $product, quantity: 1)->first()->tax_amount);
    }

    /**
     * The number that matters at settlement: PB1 is collected on the kios's
     * behalf but owed to the government, so paying them the total would
     * hand over the tax as well.
     */
    public function test_the_kios_share_excludes_the_tax_it_collected(): void
    {
        [$bazaar, $product, $vendor] = $this->stall(taxRate: 10);

        $this->sell($bazaar, $product, quantity: 2);

        $vendor = Vendor::query()
            ->withSum('sales as sales_revenue', 'price')
            ->withSum('sales as sales_tax', 'tax_amount')
            ->findOrFail($vendor->id);

        $this->assertSame(100000, (int) $vendor->sales_revenue, 'Owed to the kios');
        $this->assertSame(10000, (int) $vendor->sales_tax, 'Owed to the government');
    }

    /**
     * Every sale recorded before PB1 existed stays valid: price was always
     * the pre-tax subtotal, so a tax of zero is the truthful reading.
     */
    public function test_sales_recorded_before_tax_existed_are_still_correct(): void
    {
        $sale = VendorSale::factory()->create(['price' => 5000]);

        $this->assertSame(0, $sale->tax_amount);
        $this->assertSame(5000, $sale->total());
    }

    /**
     * @return array{0: Bazaar, 1: VendorProduct, 2: Vendor}
     */
    private function stall(float $taxRate, int $price = 50000): array
    {
        $bazaar = Bazaar::factory()->create(['is_open' => true]);
        $vendor = Vendor::factory()->create(['bazaar_id' => $bazaar->id, 'tax_rate' => $taxRate]);
        $product = VendorProduct::factory()->create([
            'vendor_id' => $vendor->id,
            'pricing_unit' => PricingUnit::PerPiece,
            'price' => $price,
        ]);

        return [$bazaar, $product, $vendor];
    }

    /**
     * @return Collection<int, VendorSale>
     */
    private function sell(Bazaar $bazaar, VendorProduct $product, int $quantity): Collection
    {
        return VendorSale::sellCartFor(
            $bazaar,
            [['product' => $product, 'quantity' => $quantity]],
            User::factory()->create(),
            TicketPaymentMethod::Cash,
        );
    }
}
