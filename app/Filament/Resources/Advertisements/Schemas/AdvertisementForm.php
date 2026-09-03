<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Materi Iklan'))
                    ->icon(Heroicon::OutlinedFilm)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label(__('Judul'))
                            ->helperText(__('Untuk referensi Anda sendiri — tidak ditampilkan di layar.'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        // A 45MB video/image dropzone needs the full row for its
                        // preview; half a column crops it to a sliver.
                        SpatieMediaLibraryFileUpload::make('file')
                            ->label(__('Video atau Gambar'))
                            ->helperText(__('Maks. 45MB.'))
                            ->collection('file')
                            // Filament file uploads default to private (signed) URLs, but
                            // the queue display screen is public with no login — it can't
                            // generate those signed URLs, so the file must be world-readable.
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm'])
                            ->maxSize(46080)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Pemutaran di Layar Antrian'))
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('duration_seconds')
                            ->label(__('Durasi Tampil (detik)'))
                            ->helperText(__('Khusus untuk gambar — video otomatis diputar sampai selesai. Kosongkan untuk pakai default (8 detik).'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120),
                        TextInput::make('sort_order')
                            ->label(__('Urutan'))
                            ->helperText(__('Angka lebih kecil tampil lebih dulu.'))
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('Aktif'))
                            ->helperText(__('Hanya iklan aktif yang ikut diputar di layar antrian.'))
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
