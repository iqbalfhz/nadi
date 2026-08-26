<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\BookingCalendarWidget;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BookingCalendar extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.booking-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Booking Room';

    protected static ?string $navigationLabel = 'Kalender Ruangan';

    protected static ?string $title = 'Kalender Ruangan';

    protected function getHeaderWidgets(): array
    {
        return [
            BookingCalendarWidget::class,
        ];
    }
}
