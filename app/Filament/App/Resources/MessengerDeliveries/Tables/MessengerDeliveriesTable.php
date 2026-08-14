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
                    ->label('No. Tracking')
                    ->weight('bold'),
                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable(),
                TextColumn::make('document_description')
                    ->label('Deskripsi Dokumen')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (MessengerDeliveryStatus $state): string => $state->label())
                    ->color(fn (MessengerDeliveryStatus $state): string => $state->color()),
                TextColumn::make('messenger.name')
                    ->label('Messenger')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                //
            ]);
    }
}
