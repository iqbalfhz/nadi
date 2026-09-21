<?php

namespace App\Filament\Resources\SecurityPatrols\Tables;

use App\Enums\PatrolReviewFlag;
use App\Filament\Actions\ViewMediaAction;
use App\Filament\Resources\SecurityPatrols\SecurityPatrolResource;
use App\Filament\Tables\FieldReportTable;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
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
                // Last, not first: most rounds carry nothing here, and a
                // column that is empty nine times out of ten should not be
                // the one the eye lands on. See PatrolReview for why none of
                // these refuse anything.
                TextColumn::make('review_flags')
                    ->label(__('Perlu Ditinjau'))
                    ->badge()
                    ->placeholder(__('—'))
                    ->formatStateUsing(fn (string $state): string => PatrolReviewFlag::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => PatrolReviewFlag::tryFrom($state)?->color() ?? 'gray'),
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
                // The working list: what a supervisor opens to decide whose
                // photos to look at. Excludes anything already reviewed, so
                // the queue empties instead of growing forever.
                Filter::make('needs_review')
                    ->label(__('Perlu Ditinjau'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('review_flags')
                        ->whereNull('reviewed_at')),
            ])
            ->recordUrl(fn (SecurityPatrol $record): string => SecurityPatrolResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewMediaAction::make('photos', 'Lihat Foto'),
                // Closes the loop. A flag that can never be cleared turns the
                // review list into a wall nobody reads, and the flags here
                // are mostly explainable — being able to say "looked at it,
                // it's fine" is what keeps the list worth opening.
                Action::make('reviewed')
                    ->label(__('Tandai Sudah Ditinjau'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('Tandai sudah ditinjau'))
                    ->modalDescription(__('Laporannya tidak diubah sama sekali — hanya hilang dari daftar "Perlu Ditinjau".'))
                    ->visible(fn (SecurityPatrol $record): bool => $record->review_flags !== null && $record->reviewed_at === null)
                    ->action(fn (SecurityPatrol $record) => $record->update(['reviewed_at' => now()])),
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
