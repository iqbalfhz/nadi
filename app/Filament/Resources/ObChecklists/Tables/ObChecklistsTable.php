<?php

namespace App\Filament\Resources\ObChecklists\Tables;

use App\Filament\Actions\ViewMediaAction;
use App\Filament\Tables\FieldReportTable;
use App\Models\ObArea;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('user.name')
                    ->label(__('Petugas'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('media_count')
                    ->label(__('Foto'))
                    ->badge(),
                TextColumn::make('notes')
                    ->label(__('Catatan'))
                    ->limit(50)
                    ->placeholder(__('—')),
                FieldReportTable::reportedAtColumn(),
                FieldReportTable::receivedAtColumn(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                FieldReportTable::dateFilter(),
                SelectFilter::make('ob_area_id')
                    ->label(__('Area/Titik'))
                    ->options(fn () => ObArea::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('user_id')
                    ->label(__('Petugas'))
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                ViewMediaAction::make('photos', 'Lihat Foto'),
            ]);
    }
}
