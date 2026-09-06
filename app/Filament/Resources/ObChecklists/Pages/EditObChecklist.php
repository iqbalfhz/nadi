<?php

namespace App\Filament\Resources\ObChecklists\Pages;

use App\Filament\Resources\ObChecklists\ObChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObChecklist extends EditRecord
{
    protected static string $resource = ObChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
