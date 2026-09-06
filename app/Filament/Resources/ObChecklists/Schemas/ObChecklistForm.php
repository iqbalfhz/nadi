<?php

namespace App\Filament\Resources\ObChecklists\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Correcting a filed report, not rewriting it.
 *
 * Only the note is editable. Everything else on this page is evidence: the
 * area is what the worker selected while standing there, the photos are the
 * proof, the times are the record of when it happened. An admin who could
 * change those could change what a worker said they did, and Riwayat
 * Aktivitas would record that it happened without being able to prevent it.
 *
 * The locked fields are still shown, because a note edited without seeing
 * what it belongs to is an edit made blind.
 */
class ObChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Tidak bisa diubah'))
                    ->description(__('Bagian ini berasal dari laporan petugas di lapangan.'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('area.name')
                            ->label(__('Area/Titik')),
                        TextEntry::make('user.name')
                            ->label(__('Petugas')),
                    ]),
                Section::make(__('Bisa dikoreksi'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('Catatan'))
                            ->helperText(__('Perbaiki salah ketik atau lengkapi keterangan. Perubahannya tercatat di Riwayat Aktivitas.'))
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
