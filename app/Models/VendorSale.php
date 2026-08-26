<?php

namespace App\Models;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use Database\Factories\VendorSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
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
            // sellCartFor() sets this explicitly so every line item in one
            // checkout shares the same number — only generate one here when
            // it wasn't already provided (e.g. bare factory-created rows).
            if ($sale->transaction_number !== null) {
                return;
            }

            $sale->transaction_number = static::generateTransactionNumber();
        });
    }

    protected static function generateTransactionNumber(): string
    {
        do {
            $number = 'BZR'.now()->format('ymd').strtoupper(Str::random(6));
        } while (static::query()->where('transaction_number', $number)->exists());

        return $number;
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
     * Checks out an entire cart in one atomic transaction — one VendorSale
     * row per line item, all sharing one transaction_number so they print
     * as a single receipt. Locks the bazaar row once (source of truth for
     * is_open) and every distinct product row involved (source of truth for
     * price/pricing_unit), re-checking is_open inside the transaction so a
     * bazaar being closed mid-checkout can't let the sale through anyway.
     * Products are locked in a consistent order (sorted by id) regardless of
     * the cart's own order, so two concurrent checkouts touching an
     * overlapping set of products can't deadlock each other. bazaar_id and
     * vendor_id are derived from each locked product, never from caller
     * input, so they can never disagree with vendor_product_id's real
     * parent.
     *
     * @param  array<int, array{product: VendorProduct, quantity: int}>  $items
     * @return Collection<int, self>
     */
    public static function sellCartFor(
        Bazaar $bazaar,
        array $items,
        User $cashier,
        TicketPaymentMethod $paymentMethod,
    ): Collection {
        return DB::transaction(function () use ($bazaar, $items, $cashier, $paymentMethod): Collection {
            /** @var Bazaar $lockedBazaar */
            $lockedBazaar = Bazaar::query()->whereKey($bazaar->id)->lockForUpdate()->firstOrFail();

            if (! $lockedBazaar->is_open) {
                throw new RuntimeException('Bazar ini sudah ditutup.');
            }

            $transactionNumber = static::generateTransactionNumber();

            return collect($items)
                ->sortBy(fn (array $item): int => $item['product']->id)
                ->map(function (array $item) use ($lockedBazaar, $cashier, $paymentMethod, $transactionNumber): self {
                    /** @var VendorProduct $lockedProduct */
                    $lockedProduct = VendorProduct::query()->whereKey($item['product']->id)->lockForUpdate()->firstOrFail();

                    // transaction_number isn't in #[Fillable], on purpose (it
                    // shouldn't be settable through any form) — create()
                    // would silently drop it, leaving the booted() hook to
                    // generate a fresh one per row instead of sharing this
                    // one. Setting it directly bypasses the mass-assignment
                    // guard the same way ShortLink::last_clicked_at does.
                    $sale = new self([
                        'bazaar_id' => $lockedBazaar->id,
                        'vendor_id' => $lockedProduct->vendor_id,
                        'vendor_product_id' => $lockedProduct->id,
                        'quantity' => $item['quantity'],
                        'pricing_unit' => $lockedProduct->pricing_unit,
                        'price' => $lockedProduct->priceFor($item['quantity']),
                        'payment_method' => $paymentMethod,
                        'sold_by' => $cashier->id,
                    ]);
                    $sale->transaction_number = $transactionNumber;
                    $sale->save();

                    return $sale;
                })
                ->values();
        });
    }
}
