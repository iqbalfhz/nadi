<?php

namespace App\Filament\Resources\MessengerDeliveries\Pages;

use App\Filament\Resources\MessengerDeliveries\MessengerDeliveryResource;
use App\Support\MediaAccessLog;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMessengerDelivery extends ViewRecord
{
    protected static string $resource = MessengerDeliveryResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        MediaAccessLog::forEvidence($this->getRecord(), 'proof', __('Foto Bukti Pengiriman'));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
