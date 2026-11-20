<?php

namespace App\Http\Controllers;

use App\Support\MediaAccessLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

abstract class Controller
{
    /**
     * Laravel 11 stopped putting this on the base controller, and until the
     * mobile API arrived nothing here needed it — the panels authorize
     * through Filament and Shield instead.
     *
     * API controllers have no such layer above them: $this->authorize() is
     * the only thing standing between a token and someone else's record, so
     * it belongs where every one of them can reach it.
     */
    use AuthorizesRequests;

    /**
     * Signed, 30-minute links to a record's photos, with the read written to the access log.
     *
     * The collections live on the private 'internal' disk, so there is no
     * permanent URL to hand out and each view mints its own.
     */
    protected function photoLinks(Model&HasMedia $record, string $collection, string $logLabel): JsonResponse
    {
        MediaAccessLog::record($record, $logLabel);

        $expiresAt = now()->addMinutes(30);

        return response()->json([
            'data' => $record->getMedia($collection)->map(fn (Media $media): array => [
                'id' => $media->uuid,
                'url' => $media->getTemporaryUrl($expiresAt),
                'expires_at' => $expiresAt->toIso8601String(),
            ]),
        ]);
    }
}
