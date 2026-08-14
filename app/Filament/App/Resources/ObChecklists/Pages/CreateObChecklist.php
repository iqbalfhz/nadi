<?php

namespace App\Filament\App\Resources\ObChecklists\Pages;

use App\Filament\App\Resources\ObChecklists\ObChecklistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateObChecklist extends CreateRecord
{
    protected static string $resource = ObChecklistResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
