<?php

namespace App\Filament\Resources\VendorSales\Tables;

use App\Enums\TicketPaymentMethod;
use App\Models\Bazaar;
use App\Models\Vendor;
use App\Models\VendorSale;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
                    ->label('No. Transaksi')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('bazaar.name')
                    ->label('Bazar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Kios')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendorProduct.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (VendorSale $record): string => number_format($record->quantity, 0, ',', '.').' '.$record->pricing_unit->unitSuffix()),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label())
                    ->color(fn (TicketPaymentMethod $state): string => $state->color()),
                TextColumn::make('price')
                    ->label('Subtotal')
                    ->money('IDR', decimalPlaces: 0)
                    ->summarize(Sum::make()->label('Jatah kios')->money('IDR', decimalPlaces: 0)),
                TextColumn::make('tax_amount')
                    ->label('PB1')
                    ->money('IDR', decimalPlaces: 0)
                    ->placeholder('—')
                    ->summarize(Sum::make()->label('PB1 terkumpul')->money('IDR', decimalPlaces: 0))
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->state(fn (VendorSale $record): int => $record->total())
                    ->money('IDR', decimalPlaces: 0)
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('soldByUser.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('bazaar_id')
                    ->label('Bazar')
                    ->options(fn () => Bazaar::query()->orderBy('name')->pluck('name', 'id'))
                    // latest('id'), not latest(): several rows can share a
                    // created_at to the second, and the tie-break is then
                    // whatever the database feels like returning.
                    ->default(fn () => $defaultToLatestBazaar ? Bazaar::query()->latest('id')->value('id') : null),
                SelectFilter::make('vendor_id')
                    ->label('Kios')
                    // Vendors aren't reusable master data (typed fresh per
                    // bazaar), so the same vendor name can legitimately recur
                    // across different bazaars — disambiguate with the
                    // bazaar name so the filter options aren't ambiguous.
                    ->options(fn () => Vendor::query()->with('bazaar')->orderBy('name')->get()
                        ->mapWithKeys(fn (Vendor $vendor): array => [$vendor->id => "{$vendor->name} ({$vendor->bazaar->name})"])),
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(fn () => collect(TicketPaymentMethod::cases())
                        ->mapWithKeys(fn (TicketPaymentMethod $method) => [$method->value => $method->label()])),
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(fn () => $defaultToToday ? now()->toDateString() : null),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(fn () => $defaultToToday ? now()->toDateString() : null),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from === now()->toDateString() && $until === now()->toDateString()) {
                            return ['Hari ini ('.now()->format('d M Y').')'];
                        }

                        $indicators = [];

                        if ($from) {
                            $indicators[] = 'Dari '.Carbon::parse($from)->format('d M Y');
                        }

                        if ($until) {
                            $indicators[] = 'Sampai '.Carbon::parse($until)->format('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ...($canEdit ? [EditAction::make()] : []),
            ]);
    }
}
