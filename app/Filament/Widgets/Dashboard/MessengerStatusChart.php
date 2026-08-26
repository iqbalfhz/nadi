<?php

namespace App\Filament\Widgets\Dashboard;

use App\Enums\MessengerDeliveryStatus;
use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Models\MessengerDelivery;
use Filament\Widgets\ChartWidget;

/**
 * Where the period's deliveries ended up. A large "Tersedia" or "Diambil
 * Messenger" slice is the useful signal here — those are tasks that were
 * created but never actually reached their destination.
 */
class MessengerStatusChart extends ChartWidget
{
    use InteractsWithDashboardFilters;

    /**
     * Hex equivalents of each status' Filament badge colour, so the chart
     * matches what the Messenger table already shows.
     *
     * @var array<string, string>
     */
    private const COLORS = [
        'available' => '#64748b',
        'picked_up' => '#f59e0b',
        'in_transit' => '#3b82f6',
        'delivered' => '#10b981',
    ];

    protected static ?int $sort = 5;

    protected ?string $heading = 'Status Pengiriman Kurir';

    protected ?string $maxHeight = '300px';

    protected ?string $emptyStateHeading = 'Belum ada pengiriman';

    protected ?string $emptyStateDescription = 'Tidak ada permintaan kirim dokumen pada periode ini.';

    public static function canView(): bool
    {
        return self::currentUserCanAny(['ViewAny:MessengerDelivery']);
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
        $totals = $this->totalsGroupedBy(MessengerDelivery::class, 'status');

        $labels = [];
        $values = [];
        $colors = [];

        // Driven by the enum's own case order (Tersedia → Terkirim) rather
        // than by whatever order the database returns, so the legend always
        // reads as the delivery lifecycle.
        foreach (MessengerDeliveryStatus::cases() as $status) {
            $total = $totals[$status->value] ?? 0.0;

            if ($total <= 0) {
                continue;
            }

            $labels[] = $status->label();
            $values[] = $total;
            $colors[] = self::COLORS[$status->value];
        }

        if ($values === []) {
            return [];
        }

        return [
            'datasets' => [[
                'label' => 'Pengiriman',
                'data' => $values,
                'backgroundColor' => $colors,
            ]],
            'labels' => $labels,
        ];
    }
}
