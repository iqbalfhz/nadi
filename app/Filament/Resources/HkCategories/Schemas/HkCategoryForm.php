<?php

namespace App\Filament\Resources\HkCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HkCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kategori HK')
                    ->description('Pengelompokan besar area housekeeping — misalnya Toilet, Public Area, Parkiran.')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('requires_floor')
                            ->label('Pakai field Lantai')
                            // The one setting here that is not obvious: it
                            // exists because Toilet already carries the floor
                            // inside the point's own name ("Lt 2 Zona A"), so
                            // a separate column there would only duplicate it.
                            ->helperText('Nyalakan kalau nama titik di kategori ini belum menyebut lantai. Kalau nama titiknya sudah seperti "Lt 2 Zona A", biarkan mati supaya pengawas tidak mengisi hal yang sama dua kali.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->helperText('Hanya kategori aktif yang muncul saat pengawas mengisi laporan.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
