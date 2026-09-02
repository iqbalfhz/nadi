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
use UnitEnum;

/**
 * @extends resource<RoomBooking>
 */
class RoomBookingResource extends Resource
{
    protected static ?string $model = RoomBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Booking Room');
    }

    public static function getModelLabel(): string
    {
        return __('Booking Ruangan');
    }

    public static function getNavigationLabel(): string
    {
        return __('Booking Saya');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Booking Saya');
    }

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
