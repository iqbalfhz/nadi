<?php

namespace App\Filament\Resources\HkAreas\Tables;

use App\Models\HkArea;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HkAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Grouped by category rather than a flat alphabetical list: with
            // roughly nine points per category, "all the toilets together" is
            // how an admin actually looks for one.
            ->defaultSort('category.name')
            ->emptyStateHeading(__('Belum ada titik'))
            ->emptyStateDescription(__('Buat kategori dulu di menu Kategori, lalu tambahkan titik-titiknya di sini.'))
            ->columns([
                TextColumn::make('category.name')
                    ->label(__('Kategori'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('Nama Titik'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean(),
                TextColumn::make('inspections_count')
                    ->label(__('Laporan Masuk'))
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('hk_category_id')
                    ->label(__('Kategori'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('Semua'))
                    ->trueLabel(__('Hanya yang aktif'))
                    ->falseLabel(__('Hanya yang nonaktif')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Points accumulate reports, and restrictOnDelete() would
                    // reject this at the database level as a raw
                    // QueryException. Tell the admin what to do instead.
                    ->before(function (HkArea $record, DeleteAction $action): void {
                        if (! $record->inspections()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->warning()
                            ->title(__('Titik tidak bisa dihapus'))
                            ->body("Titik \"{$record->name}\" sudah punya laporan masuk, jadi riwayatnya harus tetap utuh. Kalau titik ini sudah tidak diperiksa lagi, matikan tombol Aktif supaya tidak muncul saat pengawas mengisi laporan.")
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }
}
