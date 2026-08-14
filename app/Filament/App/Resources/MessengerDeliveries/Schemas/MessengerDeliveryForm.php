<?php

namespace App\Filament\App\Resources\MessengerDeliveries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MessengerDeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
