<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPaymentMethod;
use App\Filament\Tables\DateRangeFilter;
use App\Models\Event;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

/**
 * Shared between the admin ("Riwayat Penjualan") and /app ("Laporan
 * Penjualan Tiket") resources — both list the same Ticket model, just with
 * different query scoping, so the table definition itself isn't duplicated.
 */
class TicketsTable
{
    /**
     * $defaultToLatestEvent: /app's cashier-facing report pre-selects the
     * latest event so a cashier lands straight on "this event's" numbers.
     * Admin's report deliberately does NOT do this — admins need full
     * history visible by default, not narrowed to one event.
     *
     * $defaultToToday: /app's report also defaults the date filter to
     * today, since a cashier closing the register only cares about today's
     * transactions. Admin's report deliberately does NOT do this — admins
     * often need to look up past events, not just today.
     *
     * $canEdit: only the admin resource registers an 'edit' page — passing
     * true there (and only there) adds a correction action for data-entry
     * mistakes (wrong payment method, mistaken Member toggle, etc). Kept as
     * an explicit flag rather than relying on TicketPolicy::update() alone,
     * since the /app resource has no edit route for Filament to link to.
     */
    public static function configure(Table $table, bool $defaultToLatestEvent = false, bool $defaultToToday = false, bool $canEdit = false): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_number')
                    ->label(__('No. Transaksi'))
                    ->searchable()
                    ->placeholder(__('—'))
                    ->toggleable(),
                TextColumn::make('event.name')
                    ->label(__('Event'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('buyer_name')
                    ->label(__('Nama Pembeli'))
                    ->searchable(),
                IconColumn::make('is_member')
                    ->label(__('Member'))
                    ->boolean(),
                TextColumn::make('member_reference')
                    ->label(__('Barcode'))
                    ->placeholder(__('—')),
                TextColumn::make('payment_method')
                    ->label(__('Metode Bayar'))
                    ->badge()
                    ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label())
                    ->color(fn (TicketPaymentMethod $state): string => $state->color()),
                TextColumn::make('price')
                    ->label(__('Harga'))
                    ->money('IDR', decimalPlaces: 0),
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
                SelectFilter::make('event_id')
                    ->label(__('Event'))
                    ->options(fn () => Event::query()->orderBy('name')->pluck('name', 'id'))
                    // latest('id'), not latest(): several rows can share a
                    // created_at to the second, and the tie-break is then
                    // whatever the database feels like returning.
                    ->default(fn () => $defaultToLatestEvent ? Event::query()->latest('id')->value('id') : null),
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
        return ExcelExport::make('tiket')
            ->withFilename(fn (): string => 'penjualan-tiket-event-'.now()->format('Y-m-d'))
            ->withColumns([
                ExportColumn::make('transaction_number')->heading(__('No. Transaksi')),
                ExportColumn::make('event.name')->heading(__('Event')),
                ExportColumn::make('buyer_name')->heading(__('Nama Pembeli')),
                ExportColumn::make('is_member')->heading(__('Member')),
                ExportColumn::make('member_reference')->heading(__('Barcode')),
                ExportColumn::make('payment_method')
                    ->heading(__('Metode Bayar'))
                    ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label()),
                ExportColumn::make('price')->heading(__('Harga')),
                ExportColumn::make('soldByUser.name')->heading(__('Kasir')),
                ExportColumn::make('created_at')->heading(__('Waktu')),
            ]);
    }
}
