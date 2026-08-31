<?php

namespace App\Filament\Resources\HkCategories\Pages;

use App\Filament\Resources\HkCategories\HkCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHkCategories extends ListRecords
{
    protected static string $resource = HkCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
