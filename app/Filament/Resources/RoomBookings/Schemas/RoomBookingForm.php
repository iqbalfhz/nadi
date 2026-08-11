<?php

namespace App\Filament\Resources\RoomBookings\Schemas;

use App\Models\Room;
use App\Models\RoomBooking;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class RoomBookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')
                    ->label('Room')
                    ->options(fn () => Room::query()
                        ->with('area')
                        ->orderBy('name')
                        ->get()
                        ->groupBy(fn (Room $room) => $room->area->name)
                        ->map(fn (Collection $rooms) => $rooms->pluck('name', 'id')))
                    ->required()
                    ->searchable()
                    ->live(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required()
                    ->live(),
                DateTimePicker::make('ends_at')
                    ->required()
                    ->after('starts_at')
                    ->rule(fn (Get $get, ?RoomBooking $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                        $roomId = $get('room_id');
                        $startsAt = $get('starts_at');

                        if (! $roomId || ! $startsAt || ! $value) {
                            return;
                        }

                        if (RoomBooking::overlaps((int) $roomId, Carbon::parse($startsAt), Carbon::parse($value), $record?->id)) {
                            $fail('This room is already booked for part of that time range.');
                        }
                    }),
            ]);
    }
}
