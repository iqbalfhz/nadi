<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Departemen')
                    ->description('Kode departemen dipakai sebagai bagian dari nomor dokumen yang dihasilkan.')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Departemen')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Kode')
                            ->helperText('Contoh: TERE untuk Tenant Relation, EVEN untuk Promo & Event.')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->alpha()
                            ->formatStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : $state),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->helperText('Hanya departemen aktif yang bisa dipilih saat membuat dokumen baru.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
