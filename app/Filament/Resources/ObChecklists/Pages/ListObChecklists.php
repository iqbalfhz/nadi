<?php

namespace App\Filament\Resources\ObChecklists\Pages;

use App\Filament\Resources\ObChecklists\ObChecklistResource;
use Filament\Resources\Pages\ListRecords;

class ListObChecklists extends ListRecords
{
    protected static string $resource = ObChecklistResource::class;
}
