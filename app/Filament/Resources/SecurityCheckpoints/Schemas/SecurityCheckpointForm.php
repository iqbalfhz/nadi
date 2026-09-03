<?php

namespace App\Filament\Resources\SecurityCheckpoints\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SecurityCheckpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Titik Patroli'))
                    ->description(__('Tiap titik punya kode QR sendiri yang ditempel di lokasi dan discan security saat patroli.'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Nama Titik Patroli'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('Aktif'))
                            ->helperText(__('Nonaktifkan kalau titik ini sudah tidak dipakai — kode QR yang lama tidak akan bisa dipakai lagi.'))
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
