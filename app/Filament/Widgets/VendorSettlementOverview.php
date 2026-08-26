<?php

namespace App\Filament\Widgets;

use App\Models\Bazaar;
use App\Models\Vendor;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Per-vendor revenue breakdown — the settlement report used to divide
 * revenue with each external vendor booth at the end of a bazaar. A table
 * rather than stat tiles since the number of vendors per bazaar varies.
 */
class VendorSettlementOverview extends TableWidget
{
    use HasWidgetShield;

    /**
     * /app's cashier-facing page sets this to true so the breakdown matches
     * the "Hari ini" default elsewhere on the page. Admin's page leaves this
     * false: the whole bazaar's running total per vendor is what's useful
     * there.
     */
    public bool $scopeToToday = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $bazaar = Bazaar::query()->where('is_open', true)->latest()->first()
            ?? Bazaar::query()->latest()->first();

        return $table
            ->query(
                Vendor::query()
                    ->when(
                        $bazaar,
                        fn (Builder $query) => $query->where('bazaar_id', $bazaar->id),
                        fn (Builder $query) => $query->whereRaw('1 = 0'),
                    )
                    ->withCount(['sales as sales_count' => fn (Builder $query) => $query
                        ->when($this->scopeToToday, fn (Builder $q) => $q->whereDate('created_at', today()))])
                    ->withSum(['sales as sales_revenue' => fn (Builder $query) => $query
                        ->when($this->scopeToToday, fn (Builder $q) => $q->whereDate('created_at', today()))], 'price'),
            )
            ->heading('Rekap Per Kios (Settlement)')
            ->columns([
                TextColumn::make('name')
                    ->label('Kios'),
                TextColumn::make('sales_count')
                    ->label('Jumlah Transaksi'),
                TextColumn::make('sales_revenue')
                    ->label('Total Pendapatan')
                    ->money('IDR', decimalPlaces: 0),
            ])
            ->paginated(false);
    }
}
