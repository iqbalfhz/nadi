<?php

namespace App\Filament\Resources\ObChecklists\Tables;

use App\Models\ObArea;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ObChecklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('area.name')
                    ->label('Area/Titik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('media_count')
                    ->label('Foto')
                    ->badge(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Defaults to the current month so this doesn't grow into an
                // ever-longer unfiltered list — clear the dates for full history.
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()->toDateString()),
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
                SelectFilter::make('ob_area_id')
                    ->label('Area/Titik')
                    ->options(fn () => ObArea::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('user_id')
                    ->label('Petugas')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                //
            ]);
    }
}
