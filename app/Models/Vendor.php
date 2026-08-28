<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['bazaar_id', 'name', 'tax_rate'])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Kios';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
        ];
    }

    /**
     * PB1 on a line's subtotal, charged on top of the listed price.
     *
     * Per kios rather than per bazaar because vendors are independent
     * businesses: one under the turnover threshold sits at 0 while the stall
     * beside it charges 10. Rounded to the whole Rupiah, half up, the same
     * way PricingUnit::priceFor() rounds a per-100g price.
     */
    public function taxFor(int $subtotal): int
    {
        return (int) round($subtotal * (float) $this->tax_rate / 100);
    }

    public function chargesTax(): bool
    {
        return (float) $this->tax_rate > 0;
    }

    /**
     * @return BelongsTo<Bazaar, $this>
     */
    public function bazaar(): BelongsTo
    {
        return $this->belongsTo(Bazaar::class);
    }

    /**
     * Relation name must stay exactly "products" — BazaarForm's nested
     * Repeater::make('products')->relationship() targets it by that name.
     *
     * @return HasMany<VendorProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    /**
     * @return HasMany<VendorSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(VendorSale::class);
    }
}
