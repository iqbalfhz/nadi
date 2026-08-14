<?php

namespace App\Filament\Resources\ObAreas\Pages;

use App\Filament\Resources\ObAreas\ObAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObAreas extends ListRecords
{
    protected static string $resource = ObAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
