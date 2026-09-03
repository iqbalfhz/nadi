<?php

namespace App\Filament\Widgets;

use App\Models\Bazaar;
use App\Models\Vendor;
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
    /**
     * Belongs on the Bazar report pages (both panels' ListVendorSales
     * header), not on /admin's Dashboard — settlement is a per-bazaar
     * closing report, not a daily overview. Without this, discoverWidgets()
     * would register it onto the Dashboard purely because the class lives in
     * a scanned directory.
     *
     * It also deliberately does NOT use HasWidgetShield. A permission of its
     * own would be unreachable: Shield's role UI lists widgets from the
     * panel's discovered set, which $isDiscovered = false removes it from, so
     * the permission could never be granted to a new role while still being
     * enforced. It is redundant anyway — this widget only renders inside a
     * page that is already gated by its own permission.
     */
    protected static bool $isDiscovered = false;

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
        // latest('id'), not latest(): two rows created in the same second
        // would otherwise be ordered by whatever the database returns first.
        $bazaar = Bazaar::query()->where('is_open', true)->latest('id')->first()
            ?? Bazaar::query()->latest('id')->first();

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
                        ->when($this->scopeToToday, fn (Builder $q) => $q->whereDate('created_at', today()))], 'price')
                    // PB1 is collected on the kios's behalf but is owed to
                    // the government, not to them — kept in its own column so
                    // nobody settles up by reading the wrong number.
                    ->withSum(['sales as sales_tax' => fn (Builder $query) => $query
                        ->when($this->scopeToToday, fn (Builder $q) => $q->whereDate('created_at', today()))], 'tax_amount'),
            )
            ->heading(__('Rekap Per Kios (Settlement)'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Kios')),
                // Deliberately a raw row count (line items sold), not a
                // distinct transaction_number count — a vendor's own tally
                // cares about units sold, not how many carts they were part
                // of, unlike VendorSalesOverview's bazaar-wide "Total
                // Transaksi" stat.
                TextColumn::make('sales_count')
                    ->label(__('Jumlah Item Terjual')),
                TextColumn::make('sales_revenue')
                    ->label(__('Jatah Kios'))
                    ->money('IDR', decimalPlaces: 0),
                TextColumn::make('sales_tax')
                    ->label(__('PB1 Terkumpul'))
                    ->money('IDR', decimalPlaces: 0)
                    ->placeholder(__('—')),
                TextColumn::make('tax_rate')
                    ->label(__('Tarif'))
                    ->formatStateUsing(fn ($state): string => ((float) $state > 0 ? rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',').'%' : 'Tidak kena'))
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'gray'),
            ])
            ->paginated(false);
    }
}
