<?php

namespace App\Filament\Resources\QueueTickets\Tables;

use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
                // Defaults to today so this doesn't grow into an ever-longer,
                // unfiltered list — clear the dates to see the full history.
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->toDateString()),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(now()->toDateString()),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari '.Carbon::parse($data['from'])->format('d M Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai '.Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('status')
                    ->label('Status')
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
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (QueueTicket $record): bool => $record->status === QueueTicketStatus::Called)
                    ->authorize('update')
                    ->action(function (QueueTicket $record): void {
                        $record->update(['status' => QueueTicketStatus::Done, 'done_at' => Carbon::now()]);
                        Notification::make()->title('Tiket ditandai selesai.')->success()->send();
                    }),
                Action::make('markSkipped')
                    ->label('Tandai Dilewati')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (QueueTicket $record): bool => $record->status === QueueTicketStatus::Called)
                    ->authorize('update')
                    ->action(function (QueueTicket $record): void {
                        $record->update(['status' => QueueTicketStatus::Skipped, 'done_at' => Carbon::now()]);
                        Notification::make()->title('Tiket ditandai dilewati.')->success()->send();
                    }),
            ]);
    }
}
