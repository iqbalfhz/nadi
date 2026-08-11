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
                    ->label('Ruangan')
                    ->searchable(),
                TextColumn::make('room.area.name')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Status')
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
                    ->label('Batalkan')
                    ->visible(fn (RoomBooking $record): bool => ! $record->trashed()),
            ]);
    }
}
