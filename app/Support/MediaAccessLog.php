<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

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
}
