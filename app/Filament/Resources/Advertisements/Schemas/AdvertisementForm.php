<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->helperText('Untuk referensi Anda sendiri — tidak ditampilkan di layar.')
                    ->required()
                    ->maxLength(255),
                SpatieMediaLibraryFileUpload::make('file')
                    ->label('Video atau Gambar')
                    ->helperText('Maks. 45MB.')
                    ->collection('file')
                    // Filament file uploads default to private (signed) URLs, but
                    // the queue display screen is public with no login — it can't
                    // generate those signed URLs, so the file must be world-readable.
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm'])
                    ->maxSize(46080)
                    ->required(),
                TextInput::make('duration_seconds')
                    ->label('Durasi Tampil (detik)')
                    ->helperText('Khusus untuk gambar — video otomatis diputar sampai selesai. Kosongkan untuk pakai default (8 detik).')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->helperText('Angka lebih kecil tampil lebih dulu.')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Hanya iklan aktif yang ikut diputar di layar antrian.')
                    ->default(true),
            ]);
    }
}
