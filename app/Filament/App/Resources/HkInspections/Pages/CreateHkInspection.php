<?php

namespace App\Filament\App\Resources\HkInspections\Pages;

use App\Enums\HkCondition;
use App\Filament\App\Resources\HkInspections\HkInspectionResource;
use App\Jobs\SendHkInspectionToTelegram;
use App\Models\HkArea;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class CreateHkInspection extends CreateRecord
{
    protected static string $resource = HkInspectionResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('Isi Laporan HK');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Cast before the lookup: findOrFail() returns a Collection when handed
        // an array of keys, and the payload is only ever a single point.
        $area = HkArea::query()->findOrFail((int) $data['hk_area_id']);

        // Read back off the chosen point rather than trusting the category the
        // form submitted. The two are separate inputs on screen, so a stale or
        // tampered payload could otherwise file a report under a category the
        // point does not belong to — and every report in /admin is filtered by
        // category.
        $data['hk_category_id'] = $area->hk_category_id;

        // Filament already drops hidden fields from the payload, so these are
        // belt-and-braces — but they make the invariant explicit and testable:
        // a floor is only meaningful where the category asks for one, and a
        // follow-up only where there was something to follow up.
        if (! $area->category->requires_floor) {
            $data['floor'] = null;
        }

        if (! HkCondition::from($data['condition'])->needsFollowUp()) {
            $data['follow_up'] = null;
        }

        return $data;
    }

    /**
     * Dispatched after the record and its photos are committed, never before:
     * the report is the thing that must survive, and Telegram being down or
     * misconfigured is not the supervisor's problem to wait on. afterCreate()
     * runs once the media library has attached the uploads, so the job finds
     * the photos it needs.
     */
    protected function afterCreate(): void
    {
        SendHkInspectionToTelegram::dispatch($this->record->getKey());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
