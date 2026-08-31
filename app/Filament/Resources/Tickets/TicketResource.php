<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<Ticket>
 */
class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $pluralModelLabel = 'Riwayat Penjualan Tiket';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Tiket Event';

    protected static ?string $navigationLabel = 'Riwayat Penjualan';

    protected static ?string $modelLabel = 'Tiket Event';

    /**
     * @return Builder<Ticket>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['event', 'soldByUser']);
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table, canEdit: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
