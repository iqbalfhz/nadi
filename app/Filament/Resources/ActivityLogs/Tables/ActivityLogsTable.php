<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                TextColumn::make('causerUser.name')
                    ->label(__('Pelaku'))
                    // A failed login or a scheduled job has no signed-in user
                    // behind it, and that absence is itself information.
                    ->placeholder(__('Sistem / tidak login'))
                    ->searchable()
                    ->sortable(),
                // Entries made while an admin was using "Masuk sebagai" belong
                // to the employee's account but were not typed by them. Shown
                // as its own column so it is visible while scanning, not buried
                // in the detail view.
                TextColumn::make('impersonated_by')
                    ->label(__('Diwakili'))
                    ->badge()
                    ->color('warning')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->placeholder(__('—'))
                    ->state(fn (ActivityLog $record): ?string => $record->impersonatorName()),
                TextColumn::make('description')
                    ->label(__('Aktivitas'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('subject_type')
                    ->label(__('Data Terkait'))
                    ->placeholder(__('—'))
                    ->state(fn (ActivityLog $record): ?string => $record->subjectLabel())
                    ->wrap(),
                TextColumn::make('log_name')
                    ->label(__('Jenis'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ActivityLog::logNames()[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => ActivityLog::LOG_COLORS[$state] ?? 'gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Defaults to the current month so opening this page doesn't
                // mean loading a year of history — clear the dates for all.
                Filter::make('created_at')
                    ->label(__('Tanggal'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('Dari Tanggal'))
                            ->default(now()->startOfMonth()->toDateString()),
                        DatePicker::make('until')
                            ->label(__('Sampai Tanggal'))
                            ->default(now()->toDateString()),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from === now()->startOfMonth()->toDateString() && $until === now()->toDateString()) {
                            return ['Bulan ini ('.now()->format('M Y').')'];
                        }

                        $indicators = [];

                        if ($from) {
                            $indicators[] = 'Dari '.Carbon::parse($from)->format('d M Y');
                        }

                        if ($until) {
                            $indicators[] = 'Sampai '.Carbon::parse($until)->format('d M Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('log_name')
                    ->label(__('Jenis'))
                    ->options(ActivityLog::logNames()),
                SelectFilter::make('event')
                    ->label(__('Aksi'))
                    ->options([
                        'created' => 'Tambah',
                        'updated' => 'Ubah',
                        'deleted' => 'Hapus',
                    ]),
                SelectFilter::make('causer_id')
                    ->label(__('Pelaku'))
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label(__('Detail'))
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->color('gray')
                    ->modalHeading(__('Detail Aktivitas'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Tutup'))
                    ->modalContent(fn (ActivityLog $record) => view('filament.partials.activity-detail', [
                        'activity' => $record,
                    ])),
            ]);
    }
}
