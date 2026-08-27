<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Event')
                    ->icon(Heroicon::OutlinedTicket)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Event')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        // An image picker with an editor needs the full row —
                        // squeezed into one column of the 2-column form its
                        // preview and buttons wrap awkwardly.
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo Event')
                            ->helperText('Muncul di bagian atas struk tiket. Opsional.')
                            ->collection('logo')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),

                Section::make('Harga & Loket')
                    ->description('Harga di-snapshot ke tiap tiket saat terjual, jadi mengubahnya di sini tidak mengubah riwayat penjualan.')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
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
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
