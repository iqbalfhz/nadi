<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('area_id')
                    ->relationship('area', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('capacity')
                    ->numeric(),
            ]);
    }
}
