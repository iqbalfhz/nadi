<?php

namespace App\Filament\Resources\ObChecklists\Pages;

use App\Filament\Resources\ObChecklists\ObChecklistResource;
use App\Support\MediaAccessLog;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewObChecklist extends ViewRecord
{
    protected static string $resource = ObChecklistResource::class;

    /**
     * Opening this page renders the evidence photos, which means handing out
     * signed URLs to them — the same exposure as the "Lihat Foto" modal, and
     * it needs the same entry in Riwayat Aktivitas. Reading leaves no trace
     * of its own; without this, the one action that actually exposes the
     * photos would be the one thing the log couldn't see.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        MediaAccessLog::forEvidence($this->getRecord(), 'photos', __('Foto Checklist OB'));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
