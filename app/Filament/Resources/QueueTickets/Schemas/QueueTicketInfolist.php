<?php

namespace App\Filament\Resources\QueueTickets\Schemas;

use App\Enums\QueueTicketStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QueueTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('formatted_number')
                    ->label(__('Nomor')),
                TextEntry::make('category.name')
                    ->label(__('Loket')),
                TextEntry::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (QueueTicketStatus $state): string => $state->label()),
                TextEntry::make('counter_label')
                    ->label(__('Loket/Counter'))
                    ->placeholder(__('—')),
                TextEntry::make('calledByUser.name')
                    ->label(__('Dipanggil Oleh'))
                    ->placeholder(__('—')),
                TextEntry::make('called_at')
                    ->label(__('Waktu Dipanggil'))
                    ->dateTime()
                    ->placeholder(__('—')),
                TextEntry::make('done_at')
                    ->label(__('Waktu Selesai/Dilewati'))
                    ->dateTime()
                    ->placeholder(__('—')),
                TextEntry::make('created_at')
                    ->label(__('Waktu Ambil Nomor'))
                    ->dateTime(),
            ]);
    }
}
