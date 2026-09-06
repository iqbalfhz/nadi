<?php

namespace App\Filament\Resources\MessengerDeliveries\Pages;

use App\Filament\Resources\MessengerDeliveries\MessengerDeliveryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMessengerDelivery extends EditRecord
{
    protected static string $resource = MessengerDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
