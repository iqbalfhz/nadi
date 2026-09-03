<?php

namespace App\Filament\Resources\HkInspections\Tables;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Filament\Actions\ViewMediaAction;
use App\Models\HkCategory;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class HkInspectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('Kategori'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('area.name')
                    ->label(__('Titik'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('condition')
                    ->label(__('Kondisi'))
                    ->badge()
                    ->formatStateUsing(fn (HkCondition $state): string => $state->label())
                    ->color(fn (HkCondition $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('shift')
                    ->label(__('Shift'))
                    ->badge()
                    ->formatStateUsing(fn (HkShift $state): string => $state->label())
                    ->color(fn (HkShift $state): string => $state->color()),
                TextColumn::make('staff_name')
                    ->label(__('Petugas'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('Pengawas'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('floor')
                    ->label(__('Lantai'))
                    ->placeholder(__('—'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('media_count')
                    ->label(__('Foto'))
                    ->badge(),
                TextColumn::make('notes')
                    ->label(__('Keterangan'))
                    ->limit(40)
                    ->placeholder(__('—'))
                    ->toggleable(),
                TextColumn::make('follow_up')
                    ->label(__('Tindak Lanjut'))
                    ->limit(40)
                    ->placeholder(__('—'))
                    ->toggleable(),
            ])
            ->filters([
                // Defaults to the current month so this doesn't grow into an
                // ever-longer unfiltered list — clear the dates for full
                // history. Copied from ObChecklistsTable, which had the same
                // problem.
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
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari '.Carbon::parse($data['from'])->format('d M Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai '.Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('hk_category_id')
                    ->label(__('Kategori'))
                    ->options(fn (): array => HkCategory::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('condition')
                    ->label(__('Kondisi'))
                    ->options(HkCondition::options()),
                SelectFilter::make('shift')
                    ->label(__('Shift'))
                    ->options(HkShift::options()),
                SelectFilter::make('user_id')
                    ->label(__('Pengawas'))
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                ViewMediaAction::make('photos', 'Lihat Foto'),
            ]);
    }
}
