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
     * When the worker says they did it — the column a supervisor should be
     * reading, and so the one that sorts the list.
     *
     * **One time, one line.** An earlier version printed the delay underneath
     * ("diterima 7 jam 25 menit kemudian"). It was accurate and it was wrong
     * for this audience: a supervisor reading two timestamps at once asks
     * which of them is the real one, and a large number reads as a warning
     * about a report that is perfectly fine. The gap is developer detail —
     * it belongs in receivedAtColumn(), which is one click away.
     *
     * Falls back to created_at when there is no claimed time. That is not a
     * fudge: reports filed on the web are filed at a desk, so their arrival
     * time *is* when they were reported. Only phone reports can differ, and
     * every one of those carries submitted_at.
     */
    public static function reportedAtColumn(): TextColumn
    {
        return TextColumn::make('submitted_at')
            ->label(__('Dilaporkan'))
            ->state(fn (ObChecklist|SecurityPatrol|HkInspection $record): ?CarbonInterface => $record->submitted_at ?? $record->created_at)
            ->dateTime('d M Y H:i')
            // COALESCE, not the bare column: sorting on submitted_at alone
            // drops every older report to one end of the list regardless of
            // when it actually happened.
            ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                ->orderByRaw('COALESCE(submitted_at, created_at) '.($direction === 'desc' ? 'desc' : 'asc')));
    }

    /**
     * When the server heard about it. Off by default, and deliberately so:
     * this is the answer to a question most readers never ask, and putting it
     * on screen unasked was what made the list confusing.
     *
     * Switched on from the column menu when somebody genuinely needs it —
     * auditing a report, or checking that an outbox flushed.
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
}
