<?php

namespace App\Filament\Resources\ObAreas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ObAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Area / Titik OB'))
                    ->description(__('Daftar titik yang bisa dipilih OB saat submit checklist kebersihan.'))
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Nama Area/Titik'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('Aktif'))
                            ->helperText(__('Hanya area aktif yang bisa dipilih OB saat submit checklist.'))
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
