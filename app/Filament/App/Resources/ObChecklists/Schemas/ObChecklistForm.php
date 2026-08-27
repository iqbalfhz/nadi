<?php

namespace App\Filament\App\Resources\ObChecklists\Schemas;

use App\Models\ObArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ObChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Checklist Kebersihan')
                    ->description('Pilih titik yang baru selesai dikerjakan, lalu lampirkan fotonya.')
                    ->icon(Heroicon::OutlinedCamera)
                    ->columnSpanFull()
                    // Single column on purpose: this form is filled on a phone
                    // while standing at the location, and a camera dropzone
                    // squeezed into half a row is unusable there.
                    ->schema([
                        Select::make('ob_area_id')
                            ->label('Area/Titik')
                            ->options(fn () => ObArea::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->label('Foto')
                            ->helperText('Ambil langsung dari kamera atau pilih dari galeri. Bisa lebih dari satu.')
                            ->collection('photos')
                            ->image()
                            ->multiple()
                            ->maxSize(10240)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->helperText('Opsional — isi kalau ada temuan atau catatan khusus.')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
