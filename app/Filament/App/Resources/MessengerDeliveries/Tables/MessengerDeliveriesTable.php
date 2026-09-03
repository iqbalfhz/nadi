<?php

namespace App\Filament\App\Resources\MessengerDeliveries\Tables;

use App\Enums\MessengerDeliveryStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessengerDeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_number')
                    ->label(__('No. Tracking'))
                    ->weight('bold'),
                TextColumn::make('destination')
                    ->label(__('Tujuan'))
                    ->searchable(),
                TextColumn::make('document_description')
                    ->label(__('Deskripsi Dokumen'))
                    ->limit(40),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (MessengerDeliveryStatus $state): string => $state->label())
                    ->color(fn (MessengerDeliveryStatus $state): string => $state->color()),
                TextColumn::make('messenger.name')
                    ->label(__('Messenger'))
                    ->placeholder(__('—')),
                TextColumn::make('created_at')
                    ->label(__('Dibuat'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                //
            ]);
    }
}
