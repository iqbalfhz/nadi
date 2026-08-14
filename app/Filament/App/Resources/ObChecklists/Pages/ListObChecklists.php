<?php

namespace App\Filament\App\Resources\ObChecklists\Pages;

use App\Filament\App\Resources\ObChecklists\ObChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObChecklists extends ListRecords
{
    protected static string $resource = ObChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Submit Checklist'),
        ];
    }
}
