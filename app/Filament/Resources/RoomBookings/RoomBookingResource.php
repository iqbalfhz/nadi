<?php

namespace App\Filament\Resources\RoomBookings;

use App\Filament\Resources\RoomBookings\Pages\CreateRoomBooking;
use App\Filament\Resources\RoomBookings\Pages\EditRoomBooking;
use App\Filament\Resources\RoomBookings\Pages\ListRoomBookings;
use App\Filament\Resources\RoomBookings\Schemas\RoomBookingForm;
use App\Filament\Resources\RoomBookings\Tables\RoomBookingsTable;
use App\Models\RoomBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RoomBookingResource extends Resource
{
    protected static ?string $model = RoomBooking::class;

    protected static ?string $modelLabel = 'Booking Ruangan';

    protected static ?string $pluralModelLabel = 'Booking Ruangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Booking Room';

    public static function form(Schema $schema): Schema
    {
        return RoomBookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomBookingsTable::configure($table);
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
            'index' => ListRoomBookings::route('/'),
            'create' => CreateRoomBooking::route('/create'),
            'edit' => EditRoomBooking::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
