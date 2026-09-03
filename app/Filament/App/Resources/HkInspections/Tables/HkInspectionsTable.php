<?php

namespace App\Filament\App\Resources\HkInspections\Tables;

use App\Enums\HkCondition;
use App\Filament\Actions\ViewMediaAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HkInspectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('Belum ada laporan'))
            ->emptyStateDescription(__('Laporan pemeriksaan yang Anda kirim akan muncul di sini.'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('Kategori'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('area.name')
                    ->label(__('Titik'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('condition')
                    ->label(__('Kondisi'))
                    ->badge()
                    ->formatStateUsing(fn (HkCondition $state): string => $state->label())
                    ->color(fn (HkCondition $state): string => $state->color()),
                TextColumn::make('staff_name')
                    ->label(__('Petugas'))
                    ->searchable(),
            ])
            ->recordActions([
                ViewMediaAction::make('photos', 'Lihat Foto'),
            ]);
    }
}
