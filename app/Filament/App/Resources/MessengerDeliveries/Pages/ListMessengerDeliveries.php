<?php

namespace App\Filament\App\Resources\MessengerDeliveries\Pages;

use App\Filament\App\Resources\MessengerDeliveries\MessengerDeliveryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMessengerDeliveries extends ListRecords
{
    protected static string $resource = MessengerDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Kirim Dokumen'),
        ];
    }
}
