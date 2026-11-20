<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Filament\Tables\DateRangeFilter;
use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                DateRangeFilter::make(defaultFrom: now()->startOfMonth()->toDateString()),
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
