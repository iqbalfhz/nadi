<?php

namespace App\Filament\App\Resources\ShortLinks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShortLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('target_url')
                    ->label('URL yang mau dipendekkan')
                    ->url()
                    ->required()
                    ->maxLength(2048)
                    ->placeholder('https://drive.google.com/...'),
            ]);
    }
}
