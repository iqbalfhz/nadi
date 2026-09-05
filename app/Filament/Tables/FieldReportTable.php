<?php

namespace App\Filament\Tables;

use App\Models\HkInspection;
use App\Models\ObChecklist;
use App\Models\SecurityPatrol;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The two times every offline field report carries, and the date filter that
 * has to agree with them.
 *
 * A report is written where the work happens — a basement, a stairwell, a
 * parking deck — and sent whenever signal comes back. So it has two times:
 * when the worker pressed Kirim (submitted_at), and when the server heard
 * about it (created_at). The admin panel used to show only the second one,
 * which meant a 03:15 dawn round flushed at 07:00 read as a guard who started
 * work at seven. The whole offline design produced nothing for the person
 * reading it, and the reading was unfair to the worker besides.
 *
 * Shared by the three modules that file reports from a phone. Messenger is
 * deliberately not one of them: its timestamps are set by the server as each
 * transition happens, so it has no claimed time to show.
 */
class FieldReportTable
{
    /**
     * Below this, the report effectively went straight through and saying so
     * is noise. Above it, the gap is the interesting part.
     */
    private const NOTEWORTHY_DELAY_MINUTES = 2;

    /**
     * When the worker says they did it — the column a supervisor should be
     * reading, and so the one that sorts the list.
     *
     * Falls back to created_at, because submitted_at is null for every report
     * filed before the column existed and for anything the web filed. The
     * description line says which of the two is being shown, so a fallback is
     * never silently passed off as the worker's own claim.
     */
    public static function reportedAtColumn(): TextColumn
    {
        return TextColumn::make('submitted_at')
            ->label(__('Dilaporkan'))
            ->state(fn (ObChecklist|SecurityPatrol|HkInspection $record): ?CarbonInterface => $record->submitted_at ?? $record->created_at)
            ->dateTime('d M Y H:i')
            ->description(fn (ObChecklist|SecurityPatrol|HkInspection $record): ?string => self::delayLabel($record))
            // COALESCE, not the bare column: sorting on submitted_at alone
            // drops every older report to one end of the list regardless of
            // when it actually happened.
            ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                ->orderByRaw('COALESCE(submitted_at, created_at) '.($direction === 'desc' ? 'desc' : 'asc')));
    }

    /**
     * When the server heard about it. Off by default — the delay under
     * "Dilaporkan" already says whether the two differ, and this is only
     * wanted when someone needs the exact arrival time.
     */
    public static function receivedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('Diterima server'))
            ->dateTime('d M Y H:i')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Filters on the reported time, not the arrival time.
     *
     * Same reason as the column: a patrol walked at 23:58 and received at
     * 00:21 belongs to the night it was walked. Filtering on created_at hid it
     * from that day and showed it on the next one.
     *
     * Defaults to the current month so this doesn't grow into an ever-longer
     * unfiltered list — clear the dates for full history.
     */
    public static function dateFilter(): Filter
    {
        return Filter::make('reported_at')
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
                ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q
                    ->whereRaw('DATE(COALESCE(submitted_at, created_at)) >= ?', [$date]))
                ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q
                    ->whereRaw('DATE(COALESCE(submitted_at, created_at)) <= ?', [$date])))
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['from'] ?? null) {
                    $indicators[] = 'Dari '.Carbon::parse($data['from'])->format('d M Y');
                }

                if ($data['until'] ?? null) {
                    $indicators[] = 'Sampai '.Carbon::parse($data['until'])->format('d M Y');
                }

                return $indicators;
            });
    }

    /**
     * The sentence under the reported time.
     *
     * Three cases worth distinguishing, and the reader needs all three: this
     * is the worker's own time and it arrived at once; this is the worker's
     * own time and it sat in an outbox for a while; this is not the worker's
     * time at all, because none was recorded.
     */
    private static function delayLabel(ObChecklist|SecurityPatrol|HkInspection $record): ?string
    {
        if ($record->submitted_at === null) {
            return __('waktu terima server');
        }

        $minutes = (int) $record->submitted_at->diffInMinutes($record->created_at);

        if ($minutes < self::NOTEWORTHY_DELAY_MINUTES) {
            return null;
        }

        return __('diterima :selisih kemudian', [
            'selisih' => $record->submitted_at->diffForHumans($record->created_at, [
                'syntax' => Carbon::DIFF_ABSOLUTE,
                'parts' => 2,
            ]),
        ]);
    }
}
