<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use Filament\Widgets\ChartWidget;

/**
 * Which counters actually carry the queue load over the selected period —
 * the number used to decide where to put more operators.
 */
class QueueByCategoryChart extends ChartWidget
{
    use InteractsWithDashboardFilters;

    protected static ?int $sort = -5;

    protected ?string $heading = 'Antrian per Loket';

    protected ?string $maxHeight = '300px';

    protected ?string $emptyStateHeading = 'Belum ada antrian';

    protected ?string $emptyStateDescription = 'Tidak ada nomor antrian yang diambil pada periode ini.';

    public static function canView(): bool
    {
        return self::currentUserCanAny(['ViewAny:QueueTicket']);
    }

    public function getDescription(): ?string
    {
        return $this->rangeLabel();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $totals = $this->totalsGroupedBy(QueueTicket::class, 'queue_category_id');

        $names = QueueCategory::query()->pluck('name', 'id');

        $labels = [];
        $values = [];

        foreach ($totals as $categoryId => $total) {
            if ($total <= 0) {
                continue;
            }

            $name = $names->get((int) $categoryId);

            $labels[] = is_string($name) ? $name : 'Tanpa Loket';
            $values[] = $total;
        }

        if ($values === []) {
            return [];
        }

        return [
            'datasets' => [[
                'label' => 'Nomor antrian',
                'data' => $values,
                'backgroundColor' => self::palette(count($values)),
            ]],
            'labels' => $labels,
        ];
    }

    /**
     * Cycles a fixed palette so the same counter keeps a stable colour
     * between renders instead of shuffling on every refresh.
     *
     * @return array<int, string>
     */
    private static function palette(int $length): array
    {
        $palette = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#14b8a6', '#f97316', '#64748b'];

        return array_map(
            fn (int $index): string => $palette[$index % count($palette)],
            range(0, max($length - 1, 0)),
        );
    }
}
