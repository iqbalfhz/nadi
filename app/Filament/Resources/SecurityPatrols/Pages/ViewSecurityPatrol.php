<?php

namespace App\Filament\Resources\SecurityPatrols\Pages;

use App\Filament\Resources\SecurityPatrols\SecurityPatrolResource;
use App\Support\MediaAccessLog;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSecurityPatrol extends ViewRecord
{
    protected static string $resource = SecurityPatrolResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        MediaAccessLog::forEvidence($this->getRecord(), 'photos', __('Foto Patroli Security'));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
