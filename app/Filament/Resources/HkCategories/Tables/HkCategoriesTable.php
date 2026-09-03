<?php

namespace App\Filament\Resources\HkCategories\Tables;

use App\Models\HkCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HkCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Nama Kategori'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('areas_count')
                    ->label(__('Jumlah Titik'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray'),
                IconColumn::make('requires_floor')
                    ->label(__('Pakai Lantai'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean(),
                TextColumn::make('inspections_count')
                    ->label(__('Laporan Masuk'))
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // restrictOnDelete() would otherwise reject this at the
                    // database level and surface as a raw QueryException.
                    // Same guard as EditBazaar::save(), moved ahead of the
                    // query so the admin is told which rows are in the way.
                    ->before(function (HkCategory $record, DeleteAction $action): void {
                        $blocker = match (true) {
                            $record->inspections()->exists() => 'sudah punya laporan masuk',
                            $record->areas()->exists() => 'masih punya titik di dalamnya',
                            default => null,
                        };

                        if ($blocker === null) {
                            return;
                        }

                        Notification::make()
                            ->warning()
                            ->title(__('Kategori tidak bisa dihapus'))
                            ->body("Kategori \"{$record->name}\" {$blocker}. Kalau sudah tidak dipakai lagi, matikan saja tombol Aktif supaya tidak muncul saat pengawas mengisi laporan.")
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }
}
