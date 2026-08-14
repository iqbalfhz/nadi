<?php

namespace App\Filament\Resources\SecurityCheckpoints\Pages;

use App\Filament\Resources\SecurityCheckpoints\SecurityCheckpointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSecurityCheckpoints extends ListRecords
{
    protected static string $resource = SecurityCheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
