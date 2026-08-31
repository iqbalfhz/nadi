<?php

namespace App\Filament\App\Resources\Tickets;

use App\Filament\App\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * "Laporan Penjualan Tiket" — deliberately NOT scoped to Auth::id(), unlike
 * every other /app self-service resource. Any cashier needs to see the whole
 * event's combined sales (every cashier, every shift) to close out after an
 * event, not just what they personally sold.
 *
 * @extends resource<Ticket>
 */
class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $modelLabel = 'Tiket Event';

    protected static ?string $pluralModelLabel = 'Laporan Penjualan Tiket';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Tiket Event';

    protected static ?string $navigationLabel = 'Laporan Penjualan Tiket';

    /**
     * @return Builder<Ticket>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['event', 'soldByUser']);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table, defaultToLatestEvent: true, defaultToToday: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
        ];
    }
}
