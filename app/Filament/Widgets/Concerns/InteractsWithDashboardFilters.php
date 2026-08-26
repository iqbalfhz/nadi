<?php

namespace App\Filament\Widgets\Concerns;

use App\Enums\DashboardPeriod;
use App\Filament\Widgets\Support\DashboardMetric;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Shared plumbing for every widget on the /admin dashboard: it reads the
 * page-level period filter and turns it into date windows, chart buckets
 * and comparable totals, so each widget only has to describe *what* it
 * counts, never *when*.
 *
 * All date grouping goes through SQL's `DATE()`, which behaves identically
 * on MySQL (production) and SQLite (tests/local) — deliberately avoiding
 * driver-specific helpers like MONTH()/strftime().
 */
trait InteractsWithDashboardFilters
{
    use InteractsWithPageFilters;

    /** @var array{CarbonImmutable, CarbonImmutable}|null */
    protected ?array $cachedDateRange = null;

    /** @var array<string, string>|null */
    protected ?array $cachedBuckets = null;

    protected ?string $cachedGranularity = null;

    /**
     * Whether the signed-in admin holds at least one of these permissions —
     * the gate every dashboard widget uses in canView(), so a module's
     * numbers never appear to someone who can't open that module.
     *
     * @param  array<int, string>  $permissions
     */
    protected static function currentUserCanAny(array $permissions): bool
    {
        $user = Auth::user();

        if (! $user instanceof Authorizable) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    protected function dateRange(): array
    {
        if ($this->cachedDateRange !== null) {
            return $this->cachedDateRange;
        }

        $filters = $this->pageFilters ?? [];

        $period = DashboardPeriod::tryFrom(
            is_string($filters['period'] ?? null) ? $filters['period'] : '',
        ) ?? DashboardPeriod::ThisMonth;

        return $this->cachedDateRange = $period->range(
            is_string($filters['startDate'] ?? null) ? $filters['startDate'] : null,
            is_string($filters['endDate'] ?? null) ? $filters['endDate'] : null,
        );
    }

    /**
     * The equally long window immediately before the selected one — the
     * baseline every stat card's trend percentage is measured against.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    protected function previousDateRange(): array
    {
        [$start] = $this->dateRange();

        $days = $this->rangeLengthInDays();

        return [
            $start->subDays($days)->startOfDay(),
            $start->subDay()->endOfDay(),
        ];
    }

    protected function rangeLengthInDays(): int
    {
        [$start, $end] = $this->dateRange();

        return (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
    }

    protected function rangeLabel(): string
    {
        [$start, $end] = $this->dateRange();

        if ($start->isSameDay($end)) {
            return $start->format('d M Y');
        }

        return $start->format('d M Y').' — '.$end->format('d M Y');
    }

    /**
     * Charts collapse to coarser buckets on long ranges so a full year
     * doesn't render 365 unreadable columns.
     */
    protected function granularity(): string
    {
        if ($this->cachedGranularity !== null) {
            return $this->cachedGranularity;
        }

        $days = $this->rangeLengthInDays();

        return $this->cachedGranularity = match (true) {
            $days <= 31 => 'day',
            $days <= 182 => 'week',
            default => 'month',
        };
    }

    protected function granularityLabel(): string
    {
        return match ($this->granularity()) {
            'week' => 'per minggu',
            'month' => 'per bulan',
            default => 'per hari',
        };
    }

    /**
     * Ordered chart columns for the selected range: bucket key => x-axis label.
     *
     * @return array<string, string>
     */
    protected function chartBuckets(): array
    {
        if ($this->cachedBuckets !== null) {
            return $this->cachedBuckets;
        }

        [$start, $end] = $this->dateRange();

        $buckets = [];

        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $buckets[$this->bucketKeyFor($date)] ??= $this->bucketLabelFor($date);
        }

        return $this->cachedBuckets = $buckets;
    }

    /**
     * @return array<int, string>
     */
    protected function chartLabels(): array
    {
        return array_values($this->chartBuckets());
    }

    protected function bucketKeyFor(CarbonImmutable $date): string
    {
        return match ($this->granularity()) {
            'week' => $date->startOfWeek()->toDateString(),
            'month' => $date->startOfMonth()->toDateString(),
            default => $date->toDateString(),
        };
    }

    protected function bucketLabelFor(CarbonImmutable $date): string
    {
        return match ($this->granularity()) {
            'week' => $date->startOfWeek()->format('d M'),
            'month' => $date->format('M Y'),
            default => $date->format('d M'),
        };
    }

    /**
     * Totals for the selected period, the period before it, and a per-day
     * series for the card's sparkline — everything a stat card needs, in one
     * call.
     *
     * The SQL fragments ($aggregate, $dateColumn, $groupColumn) are typed
     * `literal-string` throughout this trait, so PHPStan itself enforces that
     * only hard-coded fragments — never request data — can ever reach the raw
     * selects below.
     *
     * @param  class-string<Model>  $model
     * @param  literal-string  $aggregate
     * @param  literal-string  $dateColumn
     * @param  (Closure(Builder<Model>): mixed)|null  $scope
     */
    protected function metric(
        string $model,
        string $aggregate = 'COUNT(*)',
        string $dateColumn = 'created_at',
        ?Closure $scope = null,
    ): DashboardMetric {
        [$start, $end] = $this->dateRange();
        [$previousStart, $previousEnd] = $this->previousDateRange();

        $daily = $this->dailyTotals($model, $aggregate, $dateColumn, $start, $end, $scope);
        $previousDaily = $this->dailyTotals($model, $aggregate, $dateColumn, $previousStart, $previousEnd, $scope);

        return new DashboardMetric(
            total: array_sum($daily),
            previousTotal: array_sum($previousDaily),
            series: array_values($daily),
        );
    }

    /**
     * The same numbers as metric(), folded into the chart's buckets.
     *
     * @param  class-string<Model>  $model
     * @param  literal-string  $aggregate
     * @param  literal-string  $dateColumn
     * @param  (Closure(Builder<Model>): mixed)|null  $scope
     * @return array<int, float>
     */
    protected function bucketSeries(
        string $model,
        string $aggregate = 'COUNT(*)',
        string $dateColumn = 'created_at',
        ?Closure $scope = null,
    ): array {
        [$start, $end] = $this->dateRange();

        return $this->foldIntoBuckets(
            $this->dailyTotals($model, $aggregate, $dateColumn, $start, $end, $scope),
        );
    }

    /**
     * Zero-filled day-by-day totals across the window — zero-filled so a
     * quiet Sunday reads as a dip in the chart instead of vanishing and
     * shifting every later point one column to the left.
     *
     * @param  class-string<Model>  $model
     * @param  literal-string  $aggregate
     * @param  literal-string  $dateColumn
     * @param  (Closure(Builder<Model>): mixed)|null  $scope
     * @return array<string, float>
     */
    protected function dailyTotals(
        string $model,
        string $aggregate,
        string $dateColumn,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Closure $scope = null,
    ): array {
        $query = $model::query();

        if ($scope !== null) {
            $scope($query);
        }

        $rows = $query
            ->whereBetween($dateColumn, [$start, $end])
            ->selectRaw("DATE({$dateColumn}) as bucket_date, {$aggregate} as aggregate_value")
            ->groupBy('bucket_date')
            ->pluck('aggregate_value', 'bucket_date');

        $totals = [];

        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $totals[$key] = (float) $rows->get($key, 0);
        }

        return $totals;
    }

    /**
     * @param  array<string, float>  $daily
     * @return array<int, float>
     */
    protected function foldIntoBuckets(array $daily): array
    {
        $buckets = array_fill_keys(array_keys($this->chartBuckets()), 0.0);

        foreach ($daily as $date => $value) {
            $key = $this->bucketKeyFor(CarbonImmutable::parse($date));

            $buckets[$key] = ($buckets[$key] ?? 0.0) + $value;
        }

        return array_values($buckets);
    }

    /**
     * Totals grouped by a related label instead of by date — the shape the
     * doughnut/bar breakdown charts need.
     *
     * @param  class-string<Model>  $model
     * @param  literal-string  $groupColumn
     * @param  literal-string  $aggregate
     * @param  literal-string  $dateColumn
     * @param  (Closure(Builder<Model>): mixed)|null  $scope
     * @return array<string, float>
     */
    protected function totalsGroupedBy(
        string $model,
        string $groupColumn,
        string $aggregate = 'COUNT(*)',
        string $dateColumn = 'created_at',
        ?Closure $scope = null,
    ): array {
        [$start, $end] = $this->dateRange();

        $query = $model::query();

        if ($scope !== null) {
            $scope($query);
        }

        return $query
            ->whereBetween($dateColumn, [$start, $end])
            ->selectRaw("{$groupColumn} as group_key, {$aggregate} as aggregate_value")
            ->groupBy('group_key')
            ->pluck('aggregate_value', 'group_key')
            ->mapWithKeys(fn (mixed $value, mixed $key): array => [(string) $key => (float) $value])
            ->all();
    }
}
