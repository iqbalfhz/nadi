<?php

namespace App\Console\Commands;

use App\Models\ApiIdempotencyKey;
use App\Models\ApiUpload;
use App\Models\AppCrashReport;
use Illuminate\Console\Command;

/**
 * Clears what the mobile API leaves behind.
 *
 * Three tables that only ever grow otherwise: idempotency keys, one per write
 * a phone has ever made; staged photos whose report was never sent — a worker
 * who took three photos and then abandoned the form; and crash reports, which
 * arrive without anybody asking for them.
 */
class PruneApiStaging extends Command
{
    protected $signature = 'nadi:prune-api-staging';

    protected $description = 'Hapus kunci idempotensi, foto staging API, dan laporan kegagalan lama';

    public function handle(): int
    {
        $keys = ApiIdempotencyKey::query()
            ->where('created_at', '<', now()->subDays(ApiIdempotencyKey::RETENTION_DAYS))
            ->delete();

        // One at a time, not a mass delete: each row owns a file, and a
        // deleted row whose file survives is storage nobody will ever find
        // again.
        $photos = 0;

        ApiUpload::query()
            ->where('created_at', '<', now()->subDays(ApiUpload::RETENTION_DAYS))
            ->each(function (ApiUpload $upload) use (&$photos): void {
                $upload->discard();
                $photos++;
            });

        // On last_occurred_at, not created_at: a crash group that is still
        // being hit is still live, however long ago its first sighting was.
        $crashes = AppCrashReport::query()
            ->where('last_occurred_at', '<', now()->subDays(AppCrashReport::RETENTION_DAYS))
            ->delete();

        $this->info("Kunci idempotensi dihapus: {$keys}");
        $this->info("Foto staging dihapus: {$photos}");
        $this->info("Laporan kegagalan dihapus: {$crashes}");

        return self::SUCCESS;
    }
}
