<?php

namespace App\Filament\Resources\Bazaars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BazaarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Nama Bazar'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_open')
                    ->label(__('Bazar Dibuka'))
                    ->boolean(),
                TextColumn::make('vendors_count')
                    ->label(__('Jumlah Kios')),
                TextColumn::make('sales_count')
                    ->label(__('Transaksi')),
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
