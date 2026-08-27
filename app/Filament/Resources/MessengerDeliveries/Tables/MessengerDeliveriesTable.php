<?php

namespace App\Filament\Resources\MessengerDeliveries\Tables;

use App\Enums\MessengerDeliveryStatus;
use App\Filament\Actions\ViewMediaAction;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MessengerDeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_number')
                    ->label('No. Tracking')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('sender.name')
                    ->label('Pengirim')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable(),
                TextColumn::make('document_description')
                    ->label('Deskripsi Dokumen')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (MessengerDeliveryStatus $state): string => $state->label())
                    ->color(fn (MessengerDeliveryStatus $state): string => $state->color()),
                TextColumn::make('messenger.name')
                    ->label('Messenger')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delivered_at')
                    ->label('Terkirim')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()->toDateString()),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(now()->toDateString()),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari '.Carbon::parse($data['from'])->format('d M Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai '.Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => collect(MessengerDeliveryStatus::cases())
                        ->mapWithKeys(fn (MessengerDeliveryStatus $status) => [$status->value => $status->label()])),
                SelectFilter::make('sender_id')
                    ->label('Pengirim')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('messenger_id')
                    ->label('Messenger')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                ViewMediaAction::make('proof', 'Lihat Bukti'),
            ]);
    }
}
