<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
                    ->label('No. Transaksi')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('buyer_name')
                    ->label('Nama Pembeli')
                    ->searchable(),
                IconColumn::make('is_member')
                    ->label('Member')
                    ->boolean(),
                TextColumn::make('member_reference')
                    ->label('Barcode')
                    ->placeholder('—'),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label())
                    ->color(fn (TicketPaymentMethod $state): string => $state->color()),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', decimalPlaces: 0),
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
                SelectFilter::make('event_id')
                    ->label('Event')
                    ->options(fn () => Event::query()->orderBy('name')->pluck('name', 'id'))
                    // latest('id'), not latest(): several rows can share a
                    // created_at to the second, and the tie-break is then
                    // whatever the database feels like returning.
                    ->default(fn () => $defaultToLatestEvent ? Event::query()->latest('id')->value('id') : null),
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

                        // Collapse the untouched "today" default into one
                        // readable chip instead of two separate ones.
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
