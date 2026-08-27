<?php

namespace App\Filament\App\Resources\ShortLinks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ShortLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perpendek Link')
                    ->description('Link pendeknya dibuat otomatis setelah disimpan, dan bisa dibuka siapa saja tanpa login.')
                    ->icon(Heroicon::OutlinedLink)
                    ->columnSpanFull()
                    ->schema([
                        // A Google Drive URL is far too long for half a row.
                        TextInput::make('target_url')
                            ->label('URL yang mau dipendekkan')
                            ->url()
                            ->required()
                            ->maxLength(2048)
                            ->placeholder('https://drive.google.com/...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
