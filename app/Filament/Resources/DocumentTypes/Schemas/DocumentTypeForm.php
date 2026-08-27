<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jenis Dokumen')
                    ->description('Nomor urut berjalan sendiri per jenis dokumen + PT, dan direset tiap bulan.')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Kode')
                            ->helperText('Contoh: K untuk Surat Keluar, IM untuk Internal Memo.')
                            ->required()
                            ->maxLength(4)
                            ->unique(ignoreRecord: true)
                            ->alpha()
                            ->formatStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->helperText('Hanya jenis dokumen aktif yang bisa dipilih saat membuat dokumen baru.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
