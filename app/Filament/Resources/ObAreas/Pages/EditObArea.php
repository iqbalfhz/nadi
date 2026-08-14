<?php

namespace App\Filament\Resources\ObAreas\Pages;

use App\Filament\Resources\ObAreas\ObAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObArea extends EditRecord
{
    protected static string $resource = ObAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
