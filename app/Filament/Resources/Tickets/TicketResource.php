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

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Penjualan Tiket');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tiket Event');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Penjualan');
    }

    public static function getModelLabel(): string
    {
        return __('Tiket Event');
    }

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
