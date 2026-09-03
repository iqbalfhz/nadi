<?php

namespace App\Filament\Resources\Barcodes\Tables;

use App\Enums\BarcodeFormat;
use App\Models\Barcode;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Shared between the admin (sees every employee's generated codes) and
 * /app ("Riwayat Barcode Saya", scoped to the current user) resources.
 */
class BarcodesTable
{
    public static function configure(Table $table, bool $showCreator = false): Table
    {
        return $table
            ->columns([
                TextColumn::make('format')
                    ->label(__('Jenis'))
                    ->badge()
                    ->formatStateUsing(fn (BarcodeFormat $state): string => $state->label()),
                TextColumn::make('content')
                    ->label(__('Konten'))
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('Label'))
                    ->placeholder(__('—')),
                ...($showCreator ? [
                    TextColumn::make('creator.name')
                        ->label(__('Dibuat Oleh'))
                        ->searchable(),
                ] : []),
                TextColumn::make('created_at')
                    ->label(__('Dibuat'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('format')
                    ->label(__('Jenis'))
                    ->options(fn () => collect(BarcodeFormat::cases())
                        ->mapWithKeys(fn (BarcodeFormat $format) => [$format->value => $format->label()])),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('Download'))
                    ->url(fn (Barcode $record): string => route('barcodes.download', $record))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->visible(fn (Barcode $record): bool => (Auth::user()?->can('DeleteAny:Barcode') ?? false) || Auth::id() === $record->created_by),
            ]);
    }
}
