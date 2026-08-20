<?php

namespace App\Filament\App\Resources\ShortLinks\Pages;

use App\Filament\App\Resources\ShortLinks\ShortLinkResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateShortLink extends CreateRecord
{
    protected static string $resource = ShortLinkResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
