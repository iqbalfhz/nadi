<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use App\Enums\PricingUnit;
use Database\Factories\VendorProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property PricingUnit $pricing_unit
 */
#[Fillable(['vendor_id', 'name', 'pricing_unit', 'price', 'initial_stock'])]
class VendorProduct extends Model
{
    /** @use HasFactory<VendorProductFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Produk Kios';
    }

    protected function casts(): array
    {
        return [
            'pricing_unit' => PricingUnit::class,
            'price' => 'integer',
            'initial_stock' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<VendorSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(VendorSale::class);
    }

    /**
     * Computes the total price for a given quantity, per this product's
     * pricing unit — see PricingUnit::priceFor() for the exact formula.
     */
    public function priceFor(int $quantity): int
    {
        return $this->pricing_unit->priceFor($this->price, $quantity);
    }

    /**
     * Total quantity sold across every VendorSale row for this product,
     * regardless of bazaar/transaction — there's only ever one bazaar per
     * product anyway, since products aren't reusable master data.
     */
    public function soldQuantity(): int
    {
        return (int) $this->sales()->sum('quantity');
    }

    /**
     * Null means initial_stock was never set — unlimited/untracked, per an
     * explicit admin choice for items that don't need a stock cap.
     */
    public function remainingStock(): ?int
    {
        return $this->initial_stock === null
            ? null
            : $this->initial_stock - $this->soldQuantity();
    }
}
