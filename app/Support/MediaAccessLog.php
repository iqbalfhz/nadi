<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Records that somebody looked at a record's evidence photos.
 *
 * Reading data leaves no trace of its own — nothing is written, so no model
 * event fires. Without an explicit entry, the one action that actually exposes
 * the photos would be the one thing Riwayat Aktivitas couldn't see.
 *
 * Shared by the panels (App\Filament\Actions\ViewMediaAction) and the mobile
 * API, deliberately: two copies of this would drift, and the copy that drifted
 * would be the one that quietly stopped logging.
 */
class MediaAccessLog
{
    public static function record(Model $record, string $label): void
    {
        activity('akses-data')
            ->performedOn($record)
            ->withProperty('data', $label)
            ->log('Lihat foto');
    }

    /**
     * Log a detail page that renders the photos as part of itself.
     *
     * A view page hands out the same signed URLs the modal does, just without
     * anyone having to click — so it needs the same entry. Silent when the
     * record has no photos: nothing was exposed, and an "opened the photos"
     * line for a report that has none is noise in an audit trail.
     */
    public static function forEvidence(?Model $record, string $collection, string $label): void
    {
        if (! $record instanceof HasMedia) {
            return;
        }

        if ($record->getMedia($collection)->isEmpty()) {
            return;
        }

        self::record($record, $label);
    }
}
