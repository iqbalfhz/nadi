<?php

namespace App\Filament\Resources\QueueCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QueueCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Nama'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('Kode'))
                    ->badge(),
                IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean(),
                TextColumn::make('tickets_count')
                    ->label(__('Total Tiket'))
                    ->counts('tickets')
                    ->sortable(),
            ])
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
