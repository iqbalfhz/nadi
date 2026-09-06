<?php

namespace App\Filament\Resources\SecurityPatrols\Pages;

use App\Filament\Resources\SecurityPatrols\SecurityPatrolResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSecurityPatrol extends EditRecord
{
    protected static string $resource = SecurityPatrolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
