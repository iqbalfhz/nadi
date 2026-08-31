<?php

namespace App\Filament\Resources\HkAreas\Pages;

use App\Filament\Resources\HkAreas\HkAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHkAreas extends ListRecords
{
    protected static string $resource = HkAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
