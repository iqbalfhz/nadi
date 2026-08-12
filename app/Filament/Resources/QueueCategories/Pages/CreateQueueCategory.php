<?php

namespace App\Filament\Resources\QueueCategories\Pages;

use App\Filament\Resources\QueueCategories\QueueCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQueueCategory extends CreateRecord
{
    protected static string $resource = QueueCategoryResource::class;
}
