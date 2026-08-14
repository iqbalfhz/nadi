<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Event')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('regular_price')
                    ->label('Harga Reguler')
                    ->money('IDR', decimalPlaces: 0),
                TextColumn::make('member_price')
                    ->label('Harga Member')
                    ->money('IDR', decimalPlaces: 0),
                IconColumn::make('is_open')
                    ->label('Loket Dibuka')
                    ->boolean(),
                TextColumn::make('tickets_count')
                    ->label('Tiket Terjual'),
            ])
            ->defaultSort('created_at', 'desc')
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
