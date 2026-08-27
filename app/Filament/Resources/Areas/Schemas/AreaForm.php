<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lokasi')
                    ->description('Pengelompokan ruangan, misalnya lantai atau gedung — dipakai sebagai filter di kalender booking.')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->schema([
                        // The only field on this form: without columnSpanFull()
                        // it sits in the left half of the 2-column grid with an
                        // empty right half beside it.
                        TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
