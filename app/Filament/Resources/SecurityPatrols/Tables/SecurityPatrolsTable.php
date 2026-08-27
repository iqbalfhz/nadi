<?php

namespace App\Filament\Resources\SecurityPatrols\Tables;

use App\Filament\Actions\ViewMediaAction;
use App\Models\SecurityCheckpoint;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SecurityPatrolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checkpoint.name')
                    ->label('Titik Patroli')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('media_count')
                    ->label('Foto')
                    ->badge(),
                TextColumn::make('incident_report')
                    ->label('Laporan Kejadian')
                    ->limit(50)
                    ->placeholder('—')
                    ->color(fn (?string $state): string => $state ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Waktu Kunjungan')
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
                SelectFilter::make('security_checkpoint_id')
                    ->label('Titik Patroli')
                    ->options(fn () => SecurityCheckpoint::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('user_id')
                    ->label('Petugas')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
                Filter::make('has_incident')
                    ->label('Ada Laporan Kejadian')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('incident_report')),
            ])
            ->recordActions([
                ViewMediaAction::make('photos', 'Lihat Foto'),
            ]);
    }
}
