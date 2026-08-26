<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Models\Document;
use App\Models\DocumentType;
use Filament\Widgets\ChartWidget;

/**
 * Which document types the office actually issues, so the numbering module's
 * type list can be pruned or extended based on real usage rather than guesses.
 * Horizontal bars because type names are long enough to be unreadable rotated.
 */
class DocumentsByTypeChart extends ChartWidget
{
    use InteractsWithDashboardFilters;

    protected static ?int $sort = 0;

    protected ?string $heading = 'Dokumen per Jenis';

    protected ?string $maxHeight = '300px';

    protected ?string $emptyStateHeading = 'Belum ada dokumen';

    protected ?string $emptyStateDescription = 'Tidak ada nomor dokumen yang diterbitkan pada periode ini.';

    public static function canView(): bool
    {
        return self::currentUserCanAny(['ViewAny:Document']);
    }

    public function getDescription(): ?string
    {
        return $this->rangeLabel();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $totals = $this->totalsGroupedBy(Document::class, 'document_type_id');

        arsort($totals);

        $names = DocumentType::query()->pluck('name', 'id');

        $labels = [];
        $values = [];

        foreach ($totals as $typeId => $total) {
            if ($total <= 0) {
                continue;
            }

            $name = $names->get((int) $typeId);

            $labels[] = is_string($name) ? $name : 'Tanpa Jenis';
            $values[] = $total;
        }

        if ($values === []) {
            return [];
        }

        return [
            'datasets' => [[
                'label' => 'Dokumen terbit',
                'data' => $values,
                'backgroundColor' => '#f59e0b',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
