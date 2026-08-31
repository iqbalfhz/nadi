<?php

namespace App\Filament\Resources\HkAreas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HkAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Titik HK')
                    ->description('Titik yang diperiksa pengawas — misalnya "Lt 2 Zona A" di kategori Toilet.')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('hk_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nama Titik')
                            // Free text because each category names its points
                            // its own way; there is no shared floor/zone
                            // structure to enforce here.
                            ->helperText('Pakai penamaan yang biasa dipakai di lapangan, misalnya "Lt 2 Zona A".')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->helperText('Hanya titik aktif yang muncul saat pengawas mengisi laporan.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
