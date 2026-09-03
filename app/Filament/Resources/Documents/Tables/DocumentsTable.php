<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentType;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('formatted_number')
                    ->label(__('Nomor'))
                    ->weight('bold')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('number', 'like', "%{$search}%")),
                TextColumn::make('subject')
                    ->label(__('Perihal'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('documentType.name')
                    ->label(__('Jenis Dokumen'))
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label(__('PT'))
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(__('Departemen'))
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('Dibuat Oleh')),
                TextColumn::make('created_at')
                    ->label(__('Tanggal'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Defaults to the current month so this doesn't grow into an
                // ever-longer unfiltered list — clear the dates for full history.
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

                        // Collapse the untouched default (start of this month
                        // through today) into one readable chip instead of
                        // two separate "Dari"/"Sampai" ones.
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
                SelectFilter::make('document_type_id')
                    ->label(__('Jenis Dokumen'))
                    ->options(fn () => DocumentType::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('company_id')
                    ->label(__('PT'))
                    ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('department_id')
                    ->label(__('Departemen'))
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                //
            ]);
    }
}
