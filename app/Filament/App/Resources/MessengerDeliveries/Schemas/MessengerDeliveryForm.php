<?php

namespace App\Filament\App\Resources\MessengerDeliveries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MessengerDeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permintaan Kirim Dokumen')
                    ->description('Setelah disimpan, tugas ini muncul di daftar Tugas Kurir dan bisa diambil kurir mana pun.')
                    ->icon(Heroicon::OutlinedTruck)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('destination')
                            ->label('Tujuan')
                            ->helperText('Nama atau departemen penerima.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('document_description')
                            ->label('Deskripsi Dokumen')
                            ->helperText('Contoh: Invoice PT ABC, Surat Undangan, dll.')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
