<?php

namespace App\Filament\Resources\QueueCategories\Pages;

use App\Filament\Resources\QueueCategories\QueueCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQueueCategories extends ListRecords
{
    protected static string $resource = QueueCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
