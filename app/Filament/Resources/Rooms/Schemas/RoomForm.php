<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ruangan')
                    ->icon(Heroicon::OutlinedBuildingOffice)
                    ->columnSpanFull()
                    // Three columns so all three fields fill one tidy row,
                    // instead of a 2-column grid leaving a gap beside the last.
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Ruangan')
                            ->required()
                            ->maxLength(255),
                        Select::make('area_id')
                            ->label('Lokasi')
                            ->relationship('area', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('capacity')
                            ->label('Kapasitas (orang)')
                            ->numeric()
                            ->minValue(1),
                    ]),
            ]);
    }
}
