<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\BookingCalendarWidget;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class BookingCalendar extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.booking-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Booking Room');
    }

    public static function getNavigationLabel(): string
    {
        return __('Kalender Ruangan');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Kalender Ruangan');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingCalendarWidget::class,
        ];
    }
}
