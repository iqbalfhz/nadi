<?php

namespace App\Filament\Resources\Bazaars\Pages;

use App\Filament\Resources\Bazaars\BazaarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBazaars extends ListRecords
{
    protected static string $resource = BazaarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
