<?php

namespace App\Filament\App\Resources\RoomBookings;

use App\Filament\App\Resources\RoomBookings\Pages\ListRoomBookings;
use App\Filament\App\Resources\RoomBookings\Tables\RoomBookingsTable;
use App\Models\RoomBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @extends resource<RoomBooking>
 */
class RoomBookingResource extends Resource
{
    protected static ?string $model = RoomBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Booking Saya';

    protected static ?string $navigationLabel = 'Booking Saya';

    protected static ?string $pluralModelLabel = 'Booking Saya';

    public static function table(Table $table): Table
    {
        return RoomBookingsTable::configure($table);
    }

    /**
     * @return Builder<RoomBooking>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['room.area'])
            ->where('user_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoomBookings::route('/'),
        ];
    }
}
