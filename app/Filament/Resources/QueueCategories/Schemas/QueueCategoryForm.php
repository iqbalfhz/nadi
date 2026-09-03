<?php

namespace App\Filament\Resources\QueueCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class QueueCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Loket / Layanan'))
                    ->description(__('Tiap loket punya deret nomor antriannya sendiri, direset tiap hari.'))
                    ->icon(Heroicon::OutlinedQueueList)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Nama Loket / Layanan'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label(__('Kode Prefix'))
                            ->helperText(__('Contoh: A — dipakai sebagai awalan nomor antrian (A001, A002, ...).'))
                            ->required()
                            ->maxLength(4)
                            ->unique(ignoreRecord: true)
                            ->alpha()
                            ->formatStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state),
                        Toggle::make('is_active')
                            ->label(__('Aktif'))
                            ->helperText(__('Hanya loket aktif yang muncul di kiosk & bisa dioperasikan.'))
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
