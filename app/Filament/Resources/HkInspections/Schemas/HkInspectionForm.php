<?php

namespace App\Filament\Resources\HkInspections\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Only the write-up is editable — see ObChecklistForm for why the rest is
 * locked. The condition is the supervisor's judgement at the moment they
 * stood there and the photo is what backs it; changing either from a desk
 * would be changing the finding itself, not correcting how it was written.
 *
 * Tindak lanjut is the exception that makes this page worth having: it is
 * genuinely filled in later, by whoever acted on the finding.
 */
class HkInspectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Tidak bisa diubah'))
                    ->description(__('Bagian ini adalah temuan pengawas di lapangan.'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('category.name')
                            ->label(__('Kategori')),
                        TextEntry::make('area.name')
                            ->label(__('Titik')),
                        TextEntry::make('staff_name')
                            ->label(__('Petugas Diperiksa')),
                        TextEntry::make('user.name')
                            ->label(__('Pengawas')),
                    ]),
                Section::make(__('Bisa dikoreksi'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('Keterangan'))
                            ->helperText(__('Perbaiki salah ketik atau lengkapi keterangan. Perubahannya tercatat di Riwayat Aktivitas.'))
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('follow_up')
                            ->label(__('Tindak Lanjut'))
                            ->helperText(__('Isi atau perbarui tindakan yang sudah diambil atas temuan ini.'))
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
