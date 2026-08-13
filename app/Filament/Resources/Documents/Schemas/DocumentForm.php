<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type_id')
                    ->label('Jenis Dokumen')
                    ->options(fn () => DocumentType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Select::make('company_id')
                    ->label('PT')
                    ->options(fn () => Company::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Select::make('department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Textarea::make('subject')
                    ->label('Perihal')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
