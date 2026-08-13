<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama PT')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode')
                    ->helperText('Satu huruf, contoh: E untuk EFM, S untuk SSK.')
                    ->required()
                    ->maxLength(4)
                    ->unique(ignoreRecord: true)
                    ->alpha()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Hanya PT aktif yang bisa dipilih saat membuat dokumen baru.')
                    ->default(true),
            ]);
    }
}
