<?php

namespace App\Filament\App\Resources\ShortLinks\Pages;

use App\Filament\App\Resources\ShortLinks\ShortLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShortLinks extends ListRecords
{
    protected static string $resource = ShortLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Short Link'),
        ];
    }
}
