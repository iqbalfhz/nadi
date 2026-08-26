<?php

namespace App\Models;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use Database\Factories\VendorSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @property PricingUnit $pricing_unit
 * @property TicketPaymentMethod $payment_method
 */
#[Fillable(['bazaar_id', 'vendor_id', 'vendor_product_id', 'quantity', 'pricing_unit', 'price', 'payment_method', 'sold_by'])]
class VendorSale extends Model
{
    /** @use HasFactory<VendorSaleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $sale): void {
            do {
                $number = 'BZR'.now()->format('ymd').strtoupper(Str::random(6));
            } while (static::query()->where('transaction_number', $number)->exists());

            $sale->transaction_number = $number;
        });
    }

    protected function casts(): array
    {
        return [
            'pricing_unit' => PricingUnit::class,
            'payment_method' => TicketPaymentMethod::class,
            'quantity' => 'integer',
            'price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Bazaar, $this>
     */
    public function bazaar(): BelongsTo
    {
        return $this->belongsTo(Bazaar::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<VendorProduct, $this>
     */
    public function vendorProduct(): BelongsTo
    {
        return $this->belongsTo(VendorProduct::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function soldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * Sells one line item at a vendor's booth — locks the vendor_product row
     * (source of truth for price/pricing_unit) and the bazaar row (source of
     * truth for is_open) and re-checks is_open inside the transaction, so a
     * bazaar being closed mid-sale can't let a sale through anyway. bazaar_id
     * and vendor_id are derived from the locked product, never from caller
     * input, so they can never disagree with vendor_product_id's real parent.
     */
    public static function sellFor(
        VendorProduct $product,
        User $cashier,
        int $quantity,
        TicketPaymentMethod $paymentMethod,
    ): self {
        return DB::transaction(function () use ($product, $cashier, $quantity, $paymentMethod): self {
            /** @var VendorProduct $lockedProduct */
            $lockedProduct = VendorProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $bazaarId = $lockedProduct->vendor()->value('bazaar_id');

            /** @var Bazaar $lockedBazaar */
            $lockedBazaar = Bazaar::query()->whereKey($bazaarId)->lockForUpdate()->firstOrFail();

            if (! $lockedBazaar->is_open) {
                throw new RuntimeException('Bazar ini sudah ditutup.');
            }

            return static::create([
                'bazaar_id' => $lockedBazaar->id,
                'vendor_id' => $lockedProduct->vendor_id,
                'vendor_product_id' => $lockedProduct->id,
                'quantity' => $quantity,
                'pricing_unit' => $lockedProduct->pricing_unit,
                'price' => $lockedProduct->priceFor($quantity),
                'payment_method' => $paymentMethod,
                'sold_by' => $cashier->id,
            ]);
        });
    }
}
