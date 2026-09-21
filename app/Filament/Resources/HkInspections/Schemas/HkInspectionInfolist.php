<?php

namespace App\Filament\Resources\HkInspections\Schemas;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Filament\Schemas\FieldReportEntries;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HkInspectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Pemeriksaan'))
                    ->icon(Heroicon::OutlinedSparkles)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('category.name')
                            ->label(__('Kategori'))
                            ->badge(),
                        TextEntry::make('area.name')
                            ->label(__('Titik')),
                        TextEntry::make('condition')
                            ->label(__('Kondisi'))
                            ->badge()
                            ->formatStateUsing(fn (HkCondition $state): string => $state->label())
                            ->color(fn (HkCondition $state): string => $state->color()),
                        TextEntry::make('shift')
                            ->label(__('Shift'))
                            ->badge()
                            ->formatStateUsing(fn (HkShift $state): string => $state->label())
                            ->color(fn (HkShift $state): string => $state->color()),
                        TextEntry::make('floor')
                            ->label(__('Lantai'))
                            ->placeholder(__('—')),
                    ]),
                // Two people, not one: the supervisor who inspected, and the
                // HK staff member being inspected. HK staff hold no NADI
                // account, which is why one is a name and the other a relation.
                Section::make(__('Orang'))
                    ->icon(Heroicon::OutlinedUsers)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('staff_name')
                            ->label(__('Petugas Diperiksa')),
                        TextEntry::make('user.name')
                            ->label(__('Pengawas')),
                    ]),
                Section::make(__('Keterangan'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('notes')
                            ->label(__('Keterangan'))
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                        TextEntry::make('follow_up')
                            ->label(__('Tindak Lanjut'))
                            ->placeholder(__('—'))
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
                    ->description(__('Jarak antara keduanya adalah lamanya laporan menunggu sinyal di HP pengawas.'))
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
