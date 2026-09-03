<?php

namespace App\Filament\Pages;

use App\Enums\DashboardPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Replaces Filament's stock dashboard so /admin opens on an actual report:
 * one period filter at the top, then stat cards and charts that all read
 * that same filter (see InteractsWithDashboardFilters).
 *
 * The filter state is persisted in the session by Filament's own
 * HasFilters trait, so an admin who works in "Bulan Lalu" stays there
 * across page loads instead of being reset every visit.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getTitle(): string|Htmlable
    {
        return __('Dashboard');
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan operasional seluruh modul NADI.';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('Periode Laporan'))
                    ->description(__('Semua kartu statistik dan grafik di bawah mengikuti rentang tanggal ini.'))
                    ->icon(Heroicon::OutlinedFunnel)
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('period')
                            ->label(__('Rentang'))
                            ->options(DashboardPeriod::options())
                            ->default(DashboardPeriod::ThisMonth->value)
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->live(),
                        DatePicker::make('startDate')
                            ->label(__('Dari Tanggal'))
                            ->default(now()->startOfMonth())
                            ->maxDate(now())
                            ->visible(fn (Get $get): bool => $get('period') === DashboardPeriod::Custom->value),
                        DatePicker::make('endDate')
                            ->label(__('Sampai Tanggal'))
                            ->default(now())
                            ->maxDate(now())
                            ->visible(fn (Get $get): bool => $get('period') === DashboardPeriod::Custom->value),
                    ]),
            ]);
    }
}
