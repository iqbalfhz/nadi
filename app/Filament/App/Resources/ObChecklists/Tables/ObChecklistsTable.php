<?php

namespace App\Filament\App\Resources\ObChecklists\Tables;

use App\Filament\Tables\FieldReportTable;
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
                // The worker's own list, so this matters here for a second
                // reason: they need to see that the round they walked at 03:15
                // was recorded as 03:15.
                FieldReportTable::reportedAtColumn()->label(__('Waktu')),
                FieldReportTable::receivedAtColumn(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordActions([
                //
            ]);
    }
}
