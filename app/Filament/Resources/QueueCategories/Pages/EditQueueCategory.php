<?php

namespace App\Filament\Resources\QueueCategories\Pages;

use App\Filament\Resources\QueueCategories\QueueCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQueueCategory extends EditRecord
{
    protected static string $resource = QueueCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
