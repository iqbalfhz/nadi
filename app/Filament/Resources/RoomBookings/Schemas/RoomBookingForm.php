<?php

namespace App\Filament\Resources\RoomBookings\Schemas;

use App\Models\Room;
use App\Models\RoomBooking;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class RoomBookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Detail Booking'))
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('room_id')
                            ->label(__('Ruangan'))
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
                            ->label(__('Dipesan Oleh'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        // Full width so the two date pickers below line up as
                        // one balanced row instead of leaving a gap beside it.
                        TextInput::make('title')
                            ->label(__('Keperluan'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        DateTimePicker::make('starts_at')
                            ->label(__('Mulai'))
                            ->required()
                            ->live(),
                        DateTimePicker::make('ends_at')
                            ->label(__('Selesai'))
                            ->required()
                            ->after('starts_at')
                            ->rule(fn (Get $get, ?RoomBooking $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $roomId = $get('room_id');
                                $startsAt = $get('starts_at');

                                if (! $roomId || ! $startsAt || ! $value) {
                                    return;
                                }

                                if (RoomBooking::overlaps((int) $roomId, Carbon::parse($startsAt), Carbon::parse($value), $record?->id)) {
                                    $fail('Ruangan ini sudah dibooking pada sebagian rentang waktu tersebut.');
                                }
                            }),
                    ]),
            ]);
    }
}
