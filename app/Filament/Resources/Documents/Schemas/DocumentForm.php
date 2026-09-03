<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Data Dokumen'))
                    ->description(__('Nomor urut dibuat otomatis dari jenis dokumen + PT, dan direset tiap bulan.'))
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('document_type_id')
                            ->label(__('Jenis Dokumen'))
                            ->options(fn () => DocumentType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('company_id')
                            ->label(__('PT'))
                            ->options(fn () => Company::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('department_id')
                            ->label(__('Departemen'))
                            ->options(fn () => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Textarea::make('subject')
                            ->label(__('Perihal'))
                            ->required()
                            ->maxLength(255)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
