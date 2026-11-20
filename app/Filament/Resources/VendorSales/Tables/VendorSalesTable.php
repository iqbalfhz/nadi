<?php

namespace App\Filament\Resources\VendorSales\Tables;

use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use App\Filament\Tables\DateRangeFilter;
use App\Models\Bazaar;
use App\Models\Vendor;
use App\Models\VendorSale;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

/**
 * Shared between the admin ("Riwayat Penjualan Bazar") and /app ("Laporan
 * Penjualan Bazar") resources — both list the same VendorSale model, just
 * with different query scoping/defaults, mirroring TicketsTable's pattern.
 */
class VendorSalesTable
{
    /**
     * $defaultToLatestBazaar / $defaultToToday: /app's cashier-facing report
     * pre-selects the latest bazaar and defaults to today, same rationale as
     * TicketsTable — a cashier closing the register cares about today's
     * numbers for the bazaar currently running. Admin's report leaves both
     * off: admins often need to look up past bazaars, not just today.
     *
     * $canEdit: only the admin resource registers an 'edit' page — passing
     * true there adds a correction action for data-entry mistakes, mirroring
     * TicketsTable's $canEdit.
     */
    public static function configure(
        Table $table,
        bool $defaultToLatestBazaar = false,
        bool $defaultToToday = false,
        bool $canEdit = false,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('transaction_number')
                    ->label(__('No. Transaksi'))
                    ->searchable()
                    ->placeholder(__('—'))
                    ->toggleable(),
                TextColumn::make('bazaar.name')
                    ->label(__('Bazar'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label(__('Kios'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendorProduct.name')
                    ->label(__('Produk'))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('Jumlah'))
                    ->formatStateUsing(fn (VendorSale $record): string => number_format($record->quantity, 0, ',', '.').' '.$record->pricing_unit->unitSuffix()),
                TextColumn::make('payment_method')
                    ->label(__('Metode Bayar'))
                    ->badge()
                    ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label())
                    ->color(fn (TicketPaymentMethod $state): string => $state->color()),
                TextColumn::make('price')
                    ->label(__('Subtotal'))
                    ->money('IDR', decimalPlaces: 0)
                    ->summarize(Sum::make()->label(__('Jatah kios'))->money('IDR', decimalPlaces: 0)),
                TextColumn::make('tax_amount')
                    ->label(__('PB1'))
                    ->money('IDR', decimalPlaces: 0)
                    ->placeholder(__('—'))
                    ->summarize(Sum::make()->label(__('PB1 terkumpul'))->money('IDR', decimalPlaces: 0))
                    ->toggleable(),
                TextColumn::make('total')
                    ->label(__('Total'))
                    ->state(fn (VendorSale $record): int => $record->total())
                    ->money('IDR', decimalPlaces: 0)
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('soldByUser.name')
                    ->label(__('Kasir'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('bazaar_id')
                    ->label(__('Bazar'))
                    ->options(fn () => Bazaar::query()->orderBy('name')->pluck('name', 'id'))
                    // latest('id'), not latest(): several rows can share a
                    // created_at to the second, and the tie-break is then
                    // whatever the database feels like returning.
                    ->default(fn () => $defaultToLatestBazaar ? Bazaar::query()->latest('id')->value('id') : null),
                SelectFilter::make('vendor_id')
                    ->label(__('Kios'))
                    // Vendors aren't reusable master data (typed fresh per
                    // bazaar), so the same vendor name can legitimately recur
                    // across different bazaars — disambiguate with the
                    // bazaar name so the filter options aren't ambiguous.
                    ->options(fn () => Vendor::query()->with('bazaar')->orderBy('name')->get()
                        ->mapWithKeys(fn (Vendor $vendor): array => [$vendor->id => "{$vendor->name} ({$vendor->bazaar->name})"])),
                SelectFilter::make('payment_method')
                    ->label(__('Metode Bayar'))
                    ->options(fn () => collect(TicketPaymentMethod::cases())
                        ->mapWithKeys(fn (TicketPaymentMethod $method) => [$method->value => $method->label()])),
                DateRangeFilter::make(defaultFrom: $defaultToToday ? now()->toDateString() : null),
            ])
            ->recordActions([
                ...($canEdit ? [EditAction::make()] : []),
            ]);
    }

    /**
     * The Export Excel sheet, identical in both panels.
     */
    public static function excelExport(): ExcelExport
    {
        return ExcelExport::make('penjualan-bazar')
            ->withFilename(fn (): string => 'penjualan-bazar-'.now()->format('Y-m-d'))
            ->withColumns([
                ExportColumn::make('transaction_number')->heading(__('No. Transaksi')),
                ExportColumn::make('bazaar.name')->heading(__('Bazar')),
                ExportColumn::make('vendor.name')->heading(__('Kios')),
                ExportColumn::make('vendorProduct.name')->heading(__('Produk')),
                ExportColumn::make('quantity')->heading(__('Jumlah')),
                ExportColumn::make('pricing_unit')
                    ->heading(__('Satuan'))
                    ->formatStateUsing(fn (PricingUnit $state): string => $state->label()),
                ExportColumn::make('payment_method')
                    ->heading(__('Metode Bayar'))
                    ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label()),
                ExportColumn::make('price')->heading(__('Subtotal')),
                ExportColumn::make('tax_rate')->heading(__('Tarif PB1 (%)')),
                ExportColumn::make('tax_amount')->heading(__('PB1')),
                ExportColumn::make('soldByUser.name')->heading(__('Kasir')),
                ExportColumn::make('created_at')->heading(__('Waktu')),
            ]);
    }
}
