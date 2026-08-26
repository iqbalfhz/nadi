<?php

namespace App\Models;

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
#[Fillable(['vendor_id', 'name', 'pricing_unit', 'price'])]
class VendorProduct extends Model
{
    /** @use HasFactory<VendorProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'pricing_unit' => PricingUnit::class,
            'price' => 'integer',
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
}
