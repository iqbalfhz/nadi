<?php

namespace App\Filament\Resources\QueueTickets\Tables;

use App\Enums\QueueTicketStatus;
use App\Filament\Tables\DateRangeFilter;
use App\Models\QueueTicket;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class QueueTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('formatted_number')
                    ->label(__('Nomor'))
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label(__('Loket'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (QueueTicketStatus $state): string => $state->label())
                    ->color(fn (QueueTicketStatus $state): string => match ($state) {
                        QueueTicketStatus::Waiting => 'gray',
                        QueueTicketStatus::Called => 'warning',
                        QueueTicketStatus::Done => 'success',
                        QueueTicketStatus::Skipped => 'danger',
                    }),
                TextColumn::make('counter_label')
                    ->label(__('Loket/Counter'))
                    ->placeholder(__('—')),
                TextColumn::make('calledByUser.name')
                    ->label(__('Dipanggil Oleh'))
                    ->placeholder(__('—')),
                TextColumn::make('created_at')
                    ->label(__('Diambil'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Defaults to today so this doesn't grow into an ever-longer,
                // unfiltered list — clear the dates to see the full history.
                DateRangeFilter::make(defaultFrom: now()->toDateString()),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(fn (): array => collect(QueueTicketStatus::cases())
                        ->mapWithKeys(fn (QueueTicketStatus $status) => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                // Escape hatch for tickets stuck as "Called" — e.g. the operator
                // who called it never came back to resolve it. Otherwise these
                // could only be fixed by editing the database directly.
                Action::make('markDone')
                    ->label(__('Tandai Selesai'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (QueueTicket $record): bool => $record->status === QueueTicketStatus::Called)
                    ->authorize('update')
                    ->action(function (QueueTicket $record): void {
                        $record->update(['status' => QueueTicketStatus::Done, 'done_at' => Carbon::now()]);
                        Notification::make()->title(__('Tiket ditandai selesai.'))->success()->send();
                    }),
                Action::make('markSkipped')
                    ->label(__('Tandai Dilewati'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (QueueTicket $record): bool => $record->status === QueueTicketStatus::Called)
                    ->authorize('update')
                    ->action(function (QueueTicket $record): void {
                        $record->update(['status' => QueueTicketStatus::Skipped, 'done_at' => Carbon::now()]);
                        Notification::make()->title(__('Tiket ditandai dilewati.'))->success()->send();
                    }),
            ]);
    }
}
