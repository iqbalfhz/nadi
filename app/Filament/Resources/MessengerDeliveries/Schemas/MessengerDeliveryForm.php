<?php

namespace App\Filament\Resources\MessengerDeliveries\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Unlike the other three field modules, the editable half here is the
 * *request* — where to collect from, where to take it, what it is. That is
 * office information somebody typed at a desk, and it is routinely wrong in
 * ways worth fixing: a floor number mistyped, a room that moved.
 *
 * The courier's half is what stays locked: status, who took it, and the three
 * timestamps are the record of what actually happened on foot, and the proof
 * photo is the evidence it arrived.
 */
class MessengerDeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Tidak bisa diubah'))
                    ->description(__('Bagian ini adalah catatan pengantaran oleh kurir.'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('tracking_number')
                            ->label(__('No. Tracking')),
                        TextEntry::make('sender.name')
                            ->label(__('Pengirim')),
                        TextEntry::make('messenger.name')
                            ->label(__('Messenger'))
                            ->placeholder(__('Belum diambil')),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge(),
                    ]),
                Section::make(__('Bisa dikoreksi'))
                    ->description(__('Rincian permintaan. Perubahannya tercatat di Riwayat Aktivitas.'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('origin')
                            ->label(__('Diambil Dari'))
                            ->helperText(__('Tempat kurir mengambil dokumennya. Tanpa ini kurir tahu tujuan tapi tidak tahu harus ke mana lebih dulu.'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('destination')
                            ->label(__('Tujuan'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('document_description')
                            ->label(__('Deskripsi Dokumen'))
                            ->required()
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
