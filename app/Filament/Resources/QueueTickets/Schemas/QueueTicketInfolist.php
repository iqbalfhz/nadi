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
                    ->label('Nomor'),
                TextEntry::make('category.name')
                    ->label('Loket'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (QueueTicketStatus $state): string => $state->label()),
                TextEntry::make('counter_label')
                    ->label('Loket/Counter')
                    ->placeholder('—'),
                TextEntry::make('calledByUser.name')
                    ->label('Dipanggil Oleh')
                    ->placeholder('—'),
                TextEntry::make('called_at')
                    ->label('Waktu Dipanggil')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('done_at')
                    ->label('Waktu Selesai/Dilewati')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Waktu Ambil Nomor')
                    ->dateTime(),
            ]);
    }
}
