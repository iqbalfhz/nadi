<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Departemen')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode')
                    ->helperText('Contoh: TERE untuk Tenant Relation, EVEN untuk Promo & Event.')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->alpha()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Hanya departemen aktif yang bisa dipilih saat membuat dokumen baru.')
                    ->default(true),
            ]);
    }
}
