<?php

namespace App\Filament\Resources\QueueCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QueueCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Loket / Layanan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode Prefix')
                    ->helperText('Contoh: A — dipakai sebagai awalan nomor antrian (A001, A002, ...).')
                    ->required()
                    ->maxLength(4)
                    ->unique(ignoreRecord: true)
                    ->alpha()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Hanya loket aktif yang muncul di kiosk & bisa dioperasikan.')
                    ->default(true),
            ]);
    }
}
