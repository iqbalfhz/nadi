<?php

namespace App\Filament\Resources\Advertisements\Tables;

use App\Models\Advertisement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->state(fn (Advertisement $record): string => $record->isVideo() ? 'Video' : 'Gambar')
                    ->badge()
                    ->color(fn (Advertisement $record): string => $record->isVideo() ? 'info' : 'gray'),
                TextColumn::make('duration_seconds')
                    ->label('Durasi')
                    ->suffix(' detik')
                    ->placeholder('Default (8 detik)'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
