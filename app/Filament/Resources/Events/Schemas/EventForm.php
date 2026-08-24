<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Event')
                    ->required()
                    ->maxLength(255),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label('Logo Event')
                    ->helperText('Muncul di bagian atas struk tiket. Opsional.')
                    ->collection('logo')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120),
                TextInput::make('regular_price')
                    ->label('Harga Reguler')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('member_price')
                    ->label('Harga Member')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('Rp'),
                Toggle::make('is_open')
                    ->label('Loket Dibuka')
                    ->helperText('Buka manual saat loket mulai jual tiket, tutup manual setelah selesai — tidak otomatis berdasarkan jadwal.')
                    ->default(false),
            ]);
    }
}
