<?php

namespace App\Filament\Resources\HkInspections\Pages;

use App\Filament\Resources\HkInspections\HkInspectionResource;
use App\Support\MediaAccessLog;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHkInspection extends ViewRecord
{
    protected static string $resource = HkInspectionResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        MediaAccessLog::forEvidence($this->getRecord(), 'photos', __('Foto Checklist HK'));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
