<?php

namespace App\Filament\Resources\SecurityPatrols\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Only the incident report is editable — see ObChecklistForm for why the rest
 * is locked. Which post was visited, by whom, and when are the evidence that
 * the round happened at all.
 */
class SecurityPatrolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Tidak bisa diubah'))
                    ->description(__('Bagian ini berasal dari pemindaian QR di pos.'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('checkpoint.name')
                            ->label(__('Titik Patroli')),
                        TextEntry::make('user.name')
                            ->label(__('Petugas')),
                    ]),
                Section::make(__('Bisa dikoreksi'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('incident_report')
                            ->label(__('Laporan Kejadian'))
                            ->helperText(__('Perbaiki salah ketik atau lengkapi keterangan. Perubahannya tercatat di Riwayat Aktivitas.'))
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
