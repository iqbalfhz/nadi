<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Date-range presets for the /admin dashboard filter. Every stat card and
 * chart on that page resolves its window through here, so the whole
 * dashboard always reports on one consistent period.
 */
enum DashboardPeriod: string
{
    case Today = 'today';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisYear = 'this_year';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Hari Ini',
            self::Last7Days => '7 Hari Terakhir',
            self::Last30Days => '30 Hari Terakhir',
            self::ThisMonth => 'Bulan Ini',
            self::LastMonth => 'Bulan Lalu',
            self::ThisYear => 'Tahun Ini',
            self::Custom => 'Rentang Kustom',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $period): array => [$period->value => $period->label()])
            ->all();
    }

    /**
     * Resolves this preset into a concrete [start, end] window. Presets that
     * run "up to now" stop at today rather than at the end of the calendar
     * period, so charts don't trail off into empty future buckets.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    public function range(?string $startDate = null, ?string $endDate = null): array
    {
        $today = CarbonImmutable::today();

        return match ($this) {
            self::Today => [$today->startOfDay(), $today->endOfDay()],
            self::Last7Days => [$today->subDays(6)->startOfDay(), $today->endOfDay()],
            self::Last30Days => [$today->subDays(29)->startOfDay(), $today->endOfDay()],
            self::ThisMonth => [$today->startOfMonth(), $today->endOfDay()],
            self::LastMonth => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth()->endOfDay(),
            ],
            self::ThisYear => [$today->startOfYear(), $today->endOfDay()],
            self::Custom => self::customRange($startDate, $endDate, $today),
        };
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private static function customRange(?string $startDate, ?string $endDate, CarbonImmutable $today): array
    {
        $start = self::parse($startDate)?->startOfDay() ?? $today->startOfMonth();
        $end = self::parse($endDate)?->endOfDay() ?? $today->endOfDay();

        // A half-filled custom range (only one date picked, or the two dragged
        // past each other) must never produce an inverted window — every query
        // on the dashboard would silently come back empty instead of erroring.
        return $start->greaterThan($end)
            ? [$end->startOfDay(), $start->endOfDay()]
            : [$start, $end];
    }

    /**
     * The filter state is bound to the URL query string, so these values can
     * be anything a visitor types — parse defensively rather than letting a
     * malformed date 500 the whole dashboard.
     */
    private static function parse(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
