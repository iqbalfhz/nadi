<?php

namespace App\Filament\Resources\AppCrashReports\Pages;

use App\Filament\Resources\AppCrashReports\AppCrashReportResource;
use Filament\Resources\Pages\ListRecords;

class ListAppCrashReports extends ListRecords
{
    protected static string $resource = AppCrashReportResource::class;

    public function getSubheading(): ?string
    {
        return __('Kegagalan yang dilaporkan sendiri oleh aplikasi di HP petugas. Kegagalan yang sama digabung jadi satu baris per versi aplikasi.');
    }
}
