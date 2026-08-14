<?php

namespace App\Filament\Resources\ObAreas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ObAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Area/Titik')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Hanya area aktif yang bisa dipilih OB saat submit checklist.')
                    ->default(true),
            ]);
    }
}
