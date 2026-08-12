<?php

namespace App\Filament\Resources\QueueTickets\Tables;

use App\Enums\QueueTicketStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QueueTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('formatted_number')
                    ->label('Nomor')
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label('Loket')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (QueueTicketStatus $state): string => $state->label())
                    ->color(fn (QueueTicketStatus $state): string => match ($state) {
                        QueueTicketStatus::Waiting => 'gray',
                        QueueTicketStatus::Called => 'warning',
                        QueueTicketStatus::Done => 'success',
                        QueueTicketStatus::Skipped => 'danger',
                    }),
                TextColumn::make('counter_label')
                    ->label('Loket/Counter')
                    ->placeholder('—'),
                TextColumn::make('calledByUser.name')
                    ->label('Dipanggil Oleh')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Diambil')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => collect(QueueTicketStatus::cases())
                        ->mapWithKeys(fn (QueueTicketStatus $status) => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
