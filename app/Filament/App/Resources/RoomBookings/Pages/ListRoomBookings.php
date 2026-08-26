<?php

namespace App\Filament\App\Resources\RoomBookings\Pages;

use App\Filament\App\Resources\RoomBookings\RoomBookingResource;
use App\Filament\App\Widgets\BookingCalendarWidget;
use Filament\Resources\Pages\ListRecords;

class ListRoomBookings extends ListRecords
{
    protected static string $resource = RoomBookingResource::class;

    // The calendar used to live on the /app Dashboard, but that was its only
    // route — nothing else linked to it. Booking a room happens by
    // selecting/clicking a slot on this calendar (there's no separate
    // "create" page), so it needs a real home on the resource it belongs to.
    protected function getHeaderWidgets(): array
    {
        return [
            BookingCalendarWidget::class,
        ];
    }
}
