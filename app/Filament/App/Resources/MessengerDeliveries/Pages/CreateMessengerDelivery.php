<?php

namespace App\Filament\App\Resources\MessengerDeliveries\Pages;

use App\Filament\App\Resources\MessengerDeliveries\MessengerDeliveryResource;
use App\Models\MessengerDelivery;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateMessengerDelivery extends CreateRecord
{
    protected static string $resource = MessengerDeliveryResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $sender */
        $sender = Auth::user();

        return MessengerDelivery::createFor(
            sender: $sender,
            destination: (string) $data['destination'],
            documentDescription: (string) $data['document_description'],
        );
    }
}
