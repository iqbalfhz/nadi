<?php

namespace App\Filament\App\Resources\ObChecklists\Schemas;

use App\Models\ObArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ObChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ob_area_id')
                    ->label('Area/Titik')
                    ->options(fn () => ObArea::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                SpatieMediaLibraryFileUpload::make('photos')
                    ->label('Foto')
                    ->helperText('Ambil langsung dari kamera atau pilih dari galeri. Bisa lebih dari satu.')
                    ->collection('photos')
                    ->image()
                    ->multiple()
                    ->maxSize(10240)
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->helperText('Opsional — isi kalau ada temuan atau catatan khusus.')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}
