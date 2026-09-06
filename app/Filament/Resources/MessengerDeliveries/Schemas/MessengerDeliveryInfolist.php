<?php

namespace App\Filament\Resources\MessengerDeliveries\Schemas;

use App\Enums\MessengerDeliveryStatus;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MessengerDeliveryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Permintaan'))
                    ->icon(Heroicon::OutlinedInboxArrowDown)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('tracking_number')
                            ->label(__('No. Tracking'))
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('sender.name')
                            ->label(__('Pengirim')),
                        // Origin before destination, always. A courier who
                        // reads them the wrong way round walks to the wrong
                        // end of the mall — the same ordering the app uses.
                        TextEntry::make('origin')
                            ->label(__('Diambil Dari'))
                            ->placeholder(__('—')),
                        TextEntry::make('destination')
                            ->label(__('Tujuan')),
                        TextEntry::make('document_description')
                            ->label(__('Deskripsi Dokumen'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Pengantaran'))
                    ->icon(Heroicon::OutlinedTruck)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (MessengerDeliveryStatus $state): string => $state->label())
                            ->color(fn (MessengerDeliveryStatus $state): string => $state->color()),
                        TextEntry::make('messenger.name')
                            ->label(__('Messenger'))
                            ->placeholder(__('Belum diambil')),
                        TextEntry::make('claimed_at')
                            ->label(__('Diambil'))
                            ->dateTime('d M Y H:i:s')
                            ->placeholder(__('—')),
                        TextEntry::make('in_transit_at')
                            ->label(__('Berangkat'))
                            ->dateTime('d M Y H:i:s')
                            ->placeholder(__('—')),
                        TextEntry::make('delivered_at')
                            ->label(__('Terkirim'))
                            ->dateTime('d M Y H:i:s')
                            ->placeholder(__('—')),
                        TextEntry::make('created_at')
                            ->label(__('Permintaan Dibuat'))
                            ->dateTime('d M Y H:i:s'),
                    ]),
                Section::make(__('Bukti Pengiriman'))
                    ->description(__('Satu foto — koleksi buktinya memang dibatasi satu.'))
                    ->icon(Heroicon::OutlinedCamera)
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('proof')
                            ->hiddenLabel()
                            ->collection('proof')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
