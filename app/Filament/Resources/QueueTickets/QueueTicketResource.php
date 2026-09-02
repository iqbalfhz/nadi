<?php

namespace App\Filament\Resources\QueueTickets;

use App\Filament\Resources\QueueTickets\Pages\ListQueueTickets;
use App\Filament\Resources\QueueTickets\Pages\ViewQueueTicket;
use App\Filament\Resources\QueueTickets\Schemas\QueueTicketInfolist;
use App\Filament\Resources\QueueTickets\Tables\QueueTicketsTable;
use App\Models\QueueTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QueueTicketResource extends Resource
{
    protected static ?string $model = QueueTicket::class;

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Tiket Antrian');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Antrian');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Tiket');
    }

    public static function getModelLabel(): string
    {
        return __('Tiket Antrian');
    }

    // Tickets are only ever created via the public kiosk and progressed by
    // operators — this resource exists purely so admins can browse history.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return QueueTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QueueTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQueueTickets::route('/'),
            'view' => ViewQueueTicket::route('/{record}'),
        ];
    }
}
