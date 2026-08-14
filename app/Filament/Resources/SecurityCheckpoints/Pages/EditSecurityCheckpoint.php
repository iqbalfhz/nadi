<?php

namespace App\Filament\Resources\SecurityCheckpoints\Pages;

use App\Filament\Resources\SecurityCheckpoints\SecurityCheckpointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSecurityCheckpoint extends EditRecord
{
    protected static string $resource = SecurityCheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
