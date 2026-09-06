<?php

namespace App\Filament\Resources\SecurityPatrols\Schemas;

use App\Filament\Schemas\FieldReportEntries;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SecurityPatrolInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Kunjungan'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('checkpoint.name')
                            ->label(__('Titik Patroli')),
                        TextEntry::make('user.name')
                            ->label(__('Petugas')),
                        TextEntry::make('incident_report')
                            ->label(__('Laporan Kejadian'))
                            ->placeholder(__('Tidak ada kejadian dilaporkan'))
                            ->color(fn (?string $state): string => $state ? 'danger' : 'gray')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Foto'))
                    ->icon(Heroicon::OutlinedCamera)
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('photos')
                            ->hiddenLabel()
                            ->collection('photos')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Waktu'))
                    ->description(__('Jarak antara keduanya adalah lamanya laporan menunggu sinyal di HP petugas. Tangga darurat dan area parkir justru tempat sinyal paling buruk.'))
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->schema([
                        FieldReportEntries::reportedAt()->label(__('Waktu Kunjungan')),
                        FieldReportEntries::receivedAt(),
                    ]),
            ]);
    }
}
