<?php

namespace App\Filament\Resources\SecurityCheckpoints\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SecurityCheckpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Titik Patroli')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Nonaktifkan kalau titik ini sudah tidak dipakai — kode QR yang lama tidak akan bisa dipakai lagi.')
                    ->default(true),
            ]);
    }
}
