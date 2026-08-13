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
                    ->label('Nomor')
                    ->weight('bold')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('number', 'like', "%{$search}%")),
                TextColumn::make('subject')
                    ->label('Perihal')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('documentType.name')
                    ->label('Jenis Dokumen')
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('PT')
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh'),
                TextColumn::make('created_at')
                    ->label('Tanggal')
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
                SelectFilter::make('document_type_id')
                    ->label('Jenis Dokumen')
                    ->options(fn () => DocumentType::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('company_id')
                    ->label('PT')
                    ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                //
            ]);
    }
}
