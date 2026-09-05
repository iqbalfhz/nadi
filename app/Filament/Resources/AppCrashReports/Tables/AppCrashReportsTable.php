<?php

namespace App\Filament\Resources\AppCrashReports\Tables;

use App\Models\AppCrashReport;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppCrashReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Newest first, as asked for. Not "most frequent first": a bug
            // that started an hour ago matters more than one that has been
            // counting up quietly since a version nobody runs any more.
            ->defaultSort('last_occurred_at', 'desc')
            ->emptyStateHeading(__('Belum ada laporan kegagalan'))
            ->emptyStateDescription(__('Aplikasi mengirim laporan sendiri saat gagal. Kosong di sini berarti belum ada kegagalan yang terkirim.'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->columns([
                TextColumn::make('last_occurred_at')
                    ->label(__('Terakhir Terjadi'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('occurrences')
                    ->label(__('Jumlah'))
                    ->badge()
                    // One report is somebody's bad afternoon; fifty is a
                    // screen nobody can get past.
                    ->color(fn (int $state): string => match (true) {
                        $state >= 20 => 'danger',
                        $state >= 5 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('app_version')
                    ->label(__('Versi Aplikasi'))
                    ->badge()
                    ->placeholder(__('—'))
                    ->sortable(),
                TextColumn::make('message')
                    ->label(__('Pesan'))
                    ->wrap()
                    ->limit(120)
                    ->searchable(),
                TextColumn::make('device')
                    ->label(__('Perangkat'))
                    ->placeholder(__('—'))
                    ->description(fn (AppCrashReport $record): ?string => $record->platform
                        ? trim($record->platform.' '.($record->os_version ?? ''))
                        : null)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('Pelapor Terakhir'))
                    ->placeholder(__('—'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('first_occurred_at')
                    ->label(__('Pertama Terjadi'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('app_version')
                    ->label(__('Versi Aplikasi'))
                    ->options(fn (): array => AppCrashReport::query()
                        ->whereNotNull('app_version')
                        ->distinct()
                        ->orderByDesc('app_version')
                        ->pluck('app_version', 'app_version')
                        ->all()),
                // The question actually asked when opening this page: is
                // anything broken *now*, as opposed to at some point in the
                // last six months.
                Filter::make('recent')
                    ->label(__('Tujuh Hari Terakhir'))
                    ->query(fn (Builder $query): Builder => $query
                        ->where('last_occurred_at', '>=', now()->subDays(7))),
            ])
            ->recordActions([
                Action::make('stack')
                    ->label(__('Lihat Detail'))
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->color('gray')
                    ->modalHeading(__('Detail Kegagalan'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Tutup'))
                    ->modalWidth('4xl')
                    ->modalContent(fn (AppCrashReport $record) => view('filament.partials.crash-detail', [
                        'crash' => $record,
                    ])),
            ]);
    }
}
