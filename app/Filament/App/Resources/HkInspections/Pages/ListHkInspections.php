<?php

namespace App\Filament\App\Resources\HkInspections\Pages;

use App\Filament\App\Resources\HkInspections\HkInspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHkInspections extends ListRecords
{
    protected static string $resource = HkInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('Isi Laporan')),
        ];
    }
}
