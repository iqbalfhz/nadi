<?php

namespace App\Filament\Widgets\Support;

use Filament\Support\Icons\Heroicon;

/**
 * One number on the dashboard, with everything a modern stat card needs:
 * the total for the selected period, the same total for the period
 * immediately before it (the trend line), and a per-day series (the
 * sparkline drawn inside the card).
 *
 * Lives here rather than in app/Support because its trend icon/colour are
 * Filament presentation concerns — the arithmetic below is the only part
 * worth testing on its own.
 */
final readonly class DashboardMetric
{
    /**
     * @param  array<int, float>  $series
     */
    public function __construct(
        public float $total,
        public float $previousTotal,
        public array $series = [],
    ) {}

    /**
     * Combines two metrics into one — used for headline cards that roll up
     * more than one module (e.g. total revenue = Tiket Event + Bazar).
     */
    public function plus(self $other): self
    {
        $length = max(count($this->series), count($other->series));

        $series = [];

        for ($index = 0; $index < $length; $index++) {
            $series[$index] = ($this->series[$index] ?? 0.0) + ($other->series[$index] ?? 0.0);
        }

        return new self(
            $this->total + $other->total,
            $this->previousTotal + $other->previousTotal,
            $series,
        );
    }

    /**
     * Percentage change against the previous period, or null when there is
     * nothing to compare against — dividing by a zero baseline would render
     * as "+∞%", which is noise rather than information.
     */
    public function changePercentage(): ?float
    {
        if ($this->previousTotal <= 0.0) {
            return null;
        }

        return (($this->total - $this->previousTotal) / $this->previousTotal) * 100;
    }

    public function trendDescription(): string
    {
        $change = $this->changePercentage();

        if ($change === null) {
            return $this->total > 0.0
                ? 'Baru ada aktivitas periode ini'
                : 'Belum ada aktivitas';
        }

        return sprintf(
            '%s%s%% vs periode sebelumnya',
            $change >= 0 ? '+' : '−',
            number_format(abs($change), 1, ',', '.'),
        );
    }

    public function trendIcon(): Heroicon
    {
        return match (true) {
            ($this->changePercentage() ?? 0.0) > 0.0 => Heroicon::OutlinedArrowTrendingUp,
            ($this->changePercentage() ?? 0.0) < 0.0 => Heroicon::OutlinedArrowTrendingDown,
            default => Heroicon::OutlinedMinusSmall,
        };
    }

    /**
     * Deliberately neutral ("gray") when there's no baseline: a first-ever
     * sale is not a "success" trend, it's just an absent comparison.
     */
    public function trendColor(): string
    {
        return match (true) {
            ($this->changePercentage() ?? 0.0) > 0.0 => 'success',
            ($this->changePercentage() ?? 0.0) < 0.0 => 'danger',
            default => 'gray',
        };
    }
}
