<?php

namespace App\Filament\Resources\HkInspections\Pages;

use App\Filament\Resources\HkInspections\HkInspectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHkInspection extends EditRecord
{
    protected static string $resource = HkInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
