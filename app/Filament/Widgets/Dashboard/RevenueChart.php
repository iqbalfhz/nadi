<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VendorSale;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Revenue from both POS modules stacked into one column per period, so the
 * bar height is the office's actual take and the split shows where it came
 * from — the view a settlement/closing report starts from.
 */
class RevenueChart extends ChartWidget
{
    use InteractsWithDashboardFilters;

    protected static ?int $sort = -10;

    protected ?string $heading = 'Pendapatan Tiket Event & Bazar';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return self::currentUserCanAny(['ViewAny:Ticket', 'ViewAny:VendorSale']);
    }

    public function getDescription(): ?string
    {
        return $this->rangeLabel().' · dikelompokkan '.$this->granularityLabel();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $datasets = [];

        if ($user->can('ViewAny:Ticket')) {
            $datasets[] = [
                'label' => 'Tiket Event',
                'data' => $this->bucketSeries(Ticket::class, 'SUM(price)'),
                'backgroundColor' => '#f59e0b',
            ];
        }

        if ($user->can('ViewAny:VendorSale')) {
            $datasets[] = [
                'label' => 'Bazar Kios',
                'data' => $this->bucketSeries(VendorSale::class, 'SUM(price)'),
                'backgroundColor' => '#10b981',
            ];
        }

        if ($datasets === []) {
            return [];
        }

        // Nothing sold in the whole window: show the widget's empty state
        // rather than a row of flat zero-height bars, which reads as broken.
        $hasRevenue = collect($datasets)
            ->contains(fn (array $dataset): bool => collect($dataset['data'])->sum() > 0);

        if (! $hasRevenue) {
            return [];
        }

        return [
            'datasets' => $datasets,
            'labels' => $this->chartLabels(),
        ];
    }

    protected function getOptions(): RawJs
    {
        // Raw JS rather than an options array: Chart.js needs real callbacks
        // to render axis ticks and tooltips as Rupiah.
        return RawJs::make(<<<'JS'
            {
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value),
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y),
                        },
                    },
                },
            }
            JS);
    }
}
