<?php

namespace App\Filament\Resources\SecurityCheckpoints\Tables;

use App\Models\SecurityCheckpoint;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SecurityCheckpointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Nama Titik Patroli'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('qrCode')
                    ->label(__('Unduh QR'))
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->url(fn (SecurityCheckpoint $record): string => route('security-checkpoints.qr-code', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
