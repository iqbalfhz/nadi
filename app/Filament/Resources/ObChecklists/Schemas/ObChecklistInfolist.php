<?php

namespace App\Filament\Resources\ObChecklists\Schemas;

use App\Filament\Schemas\FieldReportEntries;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ObChecklistInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Laporan'))
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('area.name')
                            ->label(__('Area/Titik')),
                        TextEntry::make('user.name')
                            ->label(__('Petugas')),
                        TextEntry::make('notes')
                            ->label(__('Catatan'))
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Foto'))
                    ->icon(Heroicon::OutlinedCamera)
                    ->columnSpanFull()
                    ->schema([
                        // 'private' is what makes this hand out signed URLs
                        // instead of a permanent /storage path. These files
                        // sit on the internal disk precisely so they have no
                        // public address — see config/filesystems.php.
                        SpatieMediaLibraryImageEntry::make('photos')
                            ->hiddenLabel()
                            ->collection('photos')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Waktu'))
                    ->description(__('Jarak antara keduanya adalah lamanya laporan menunggu sinyal di HP petugas.'))
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->schema([
                        FieldReportEntries::reportedAt(),
                        FieldReportEntries::receivedAt(),
                        FieldReportEntries::claimedAt(),
                    ]),
            ]);
    }
}
