<?php

namespace App\Filament\App\Resources\ObChecklists\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ObChecklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('area.name')
                    ->label(__('Area/Titik'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label(__('Catatan'))
                    ->limit(50)
                    ->placeholder(__('—')),
                TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                //
            ]);
    }
}
