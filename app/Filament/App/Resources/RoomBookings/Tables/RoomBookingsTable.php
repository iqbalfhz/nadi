<?php

namespace App\Filament\App\Resources\RoomBookings\Tables;

use App\Models\RoomBooking;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RoomBookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room.name')
                    ->label(__('Ruangan'))
                    ->searchable(),
                TextColumn::make('room.area.name')
                    ->label(__('Lokasi'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('Judul'))
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label(__('Mulai'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('Selesai'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (RoomBooking $record): string => $record->trashed() ? 'Dibatalkan' : 'Aktif')
                    ->badge()
                    ->color(fn (RoomBooking $record): string => $record->trashed() ? 'danger' : 'success'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('Batalkan'))
                    ->visible(fn (RoomBooking $record): bool => ! $record->trashed()),
            ]);
    }
}
