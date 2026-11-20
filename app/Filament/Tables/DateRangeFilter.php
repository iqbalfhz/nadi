<?php

namespace App\Filament\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The "Dari / Sampai Tanggal" filter shared by every report table.
 */
class DateRangeFilter
{
    /**
     * @param  string|null  $defaultFrom  Pre-filled start date; when set, the end date defaults to today.
     * @param  string|Expression|null  $column  Column to compare, defaults to $name.
     */
    public static function make(string $name = 'created_at', ?string $defaultFrom = null, string|Expression|null $column = null): Filter
    {
        $column ??= $name;

        return Filter::make($name)
            ->label(__('Tanggal'))
            ->schema([
                DatePicker::make('from')
                    ->label(__('Dari Tanggal'))
                    ->default($defaultFrom),
                DatePicker::make('until')
                    ->label(__('Sampai Tanggal'))
                    ->default($defaultFrom === null ? null : now()->toDateString()),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate($column, '<=', $date)))
            ->indicateUsing(fn (array $data): array => self::indicators($data['from'] ?? null, $data['until'] ?? null));
    }

    /**
     * @return array<int, string>
     */
    private static function indicators(?string $from, ?string $until): array
    {
        $today = now()->toDateString();

        // The two common defaults read better as one chip than as two.
        if ($until === $today && $from === $today) {
            return [__('Hari ini (:tanggal)', ['tanggal' => now()->translatedFormat('d M Y')])];
        }

        if ($until === $today && $from === now()->startOfMonth()->toDateString()) {
            return [__('Bulan ini (:bulan)', ['bulan' => now()->translatedFormat('M Y')])];
        }

        $indicators = [];

        if ($from) {
            $indicators[] = __('Dari :tanggal', ['tanggal' => Carbon::parse($from)->translatedFormat('d M Y')]);
        }

        if ($until) {
            $indicators[] = __('Sampai :tanggal', ['tanggal' => Carbon::parse($until)->translatedFormat('d M Y')]);
        }

        return $indicators;
    }
}
