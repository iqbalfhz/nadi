<?php

namespace App\Filament\Widgets\Dashboard;

use App\Enums\QueueTicketStatus;
use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Models\Document;
use App\Models\MessengerDelivery;
use App\Models\ObChecklist;
use App\Models\QueueTicket;
use App\Models\RoomBooking;
use App\Models\SecurityPatrol;
use App\Models\User;
use Closure;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * One line per operational module over the selected period — the "is the
 * office busier than last month" view that no single module's own report
 * can give, since each of those only ever sees its own table.
 */
class ModuleActivityChart extends ChartWidget
{
    use InteractsWithDashboardFilters;

    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'ViewAny:Document',
        'ViewAny:RoomBooking',
        'ViewAny:QueueTicket',
        'ViewAny:ObChecklist',
        'ViewAny:SecurityPatrol',
        'ViewAny:MessengerDelivery',
    ];

    protected static ?int $sort = -20;

    protected ?string $heading = 'Tren Aktivitas Modul';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return self::currentUserCanAny(self::PERMISSIONS);
    }

    public function getDescription(): ?string
    {
        return $this->rangeLabel().' · dikelompokkan '.$this->granularityLabel();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $datasets = [];

        foreach ($this->modules() as $module) {
            if (! $user->can($module['permission'])) {
                continue;
            }

            $datasets[] = [
                'label' => $module['label'],
                'data' => $this->bucketSeries(
                    $module['model'],
                    dateColumn: $module['dateColumn'],
                    scope: $module['scope'],
                ),
                'borderColor' => $module['color'],
                'backgroundColor' => $module['color'],
                'tension' => 0.35,
                'fill' => false,
            ];
        }

        if ($datasets === []) {
            return [];
        }

        return [
            'datasets' => $datasets,
            'labels' => $this->chartLabels(),
        ];
    }

    /**
     * @return array<int, array{label: string, model: class-string<Model>, permission: string, color: string, dateColumn: literal-string, scope: (Closure(Builder<Model>): mixed)|null}>
     */
    private function modules(): array
    {
        return [
            [
                'label' => 'Dokumen',
                'model' => Document::class,
                'permission' => 'ViewAny:Document',
                'color' => '#f59e0b',
                'dateColumn' => 'created_at',
                'scope' => null,
            ],
            [
                'label' => 'Booking Ruangan',
                'model' => RoomBooking::class,
                'permission' => 'ViewAny:RoomBooking',
                'color' => '#3b82f6',
                'dateColumn' => 'starts_at',
                'scope' => null,
            ],
            [
                'label' => 'Antrian Dilayani',
                'model' => QueueTicket::class,
                'permission' => 'ViewAny:QueueTicket',
                'color' => '#8b5cf6',
                'dateColumn' => 'created_at',
                'scope' => fn (Builder $query) => $query->where('status', QueueTicketStatus::Done),
            ],
            [
                'label' => 'Checklist OB',
                'model' => ObChecklist::class,
                'permission' => 'ViewAny:ObChecklist',
                'color' => '#10b981',
                'dateColumn' => 'created_at',
                'scope' => null,
            ],
            [
                'label' => 'Scan Patroli',
                'model' => SecurityPatrol::class,
                'permission' => 'ViewAny:SecurityPatrol',
                'color' => '#ef4444',
                'dateColumn' => 'created_at',
                'scope' => null,
            ],
            [
                'label' => 'Pengiriman Kurir',
                'model' => MessengerDelivery::class,
                'permission' => 'ViewAny:MessengerDelivery',
                'color' => '#64748b',
                'dateColumn' => 'created_at',
                'scope' => null,
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // Activity counts are whole records — a "2.5 dokumen"
                    // gridline would be nonsense.
                    'ticks' => ['precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
