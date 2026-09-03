<?php

namespace App\Enums;

enum PricingUnit: string
{
    case PerHundredGrams = 'per_100g';
    case PerPiece = 'per_pcs';

    public function label(): string
    {
        return match ($this) {
            self::PerHundredGrams => __('Per 100 gram'),
            self::PerPiece => __('Per pcs'),
        };
    }

    /**
     * Suffix used on receipts/reports, e.g. "350 gram" / "3 pcs".
     */
    public function unitSuffix(): string
    {
        return match ($this) {
            self::PerHundredGrams => __('gram'),
            self::PerPiece => __('pcs'),
        };
    }

    /**
     * Label for the POS page's quantity input, swapped based on the
     * selected product's pricing unit.
     */
    public function quantityFieldLabel(): string
    {
        return match ($this) {
            self::PerHundredGrams => __('Berat (gram)'),
            self::PerPiece => __('Jumlah (pcs)'),
        };
    }

    /**
     * Computes the total price for $quantity units of a product priced at
     * $unitPrice (per 100g, or per single pcs, depending on this case).
     *
     * PerHundredGrams: exact proportional value — grams ÷ 100 × price_per_100g
     * — then rounded to the nearest whole Rupiah (never bucketed to the
     * nearest 100g first). PHP round()'s default mode is already half-up.
     *
     * PerPiece: plain qty × unit price — both operands are integers, so this
     * is always exact, no rounding needed.
     */
    public function priceFor(int $unitPrice, int $quantity): int
    {
        return match ($this) {
            self::PerHundredGrams => (int) round($quantity * $unitPrice / 100),
            self::PerPiece => $quantity * $unitPrice,
        };
    }
}
