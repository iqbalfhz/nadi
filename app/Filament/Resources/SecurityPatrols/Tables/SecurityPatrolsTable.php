<?php

namespace App\Filament\Resources\SecurityPatrols\Tables;

use App\Filament\Actions\ViewMediaAction;
use App\Filament\Resources\SecurityPatrols\SecurityPatrolResource;
use App\Filament\Tables\FieldReportTable;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityPatrolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checkpoint.name')
                    ->label(__('Titik Patroli'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('Petugas'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('media_count')
                    ->label(__('Foto'))
                    ->badge(),
                TextColumn::make('incident_report')
                    ->label(__('Laporan Kejadian'))
                    ->limit(50)
                    ->placeholder(__('—'))
                    ->color(fn (?string $state): string => $state ? 'danger' : 'gray'),
                // "Waktu Kunjungan" is the time the guard reached the post,
                // which is submitted_at — not when the report got out of the
                // basement.
                FieldReportTable::reportedAtColumn()->label(__('Waktu Kunjungan')),
                FieldReportTable::receivedAtColumn(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                FieldReportTable::dateFilter(),
                SelectFilter::make('security_checkpoint_id')
                    ->label(__('Titik Patroli'))
                    ->options(fn () => SecurityCheckpoint::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('user_id')
                    ->label(__('Petugas'))
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
                Filter::make('has_incident')
                    ->label(__('Ada Laporan Kejadian'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('incident_report')),
            ])
            ->recordUrl(fn (SecurityPatrol $record): string => SecurityPatrolResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewMediaAction::make('photos', 'Lihat Foto'),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
