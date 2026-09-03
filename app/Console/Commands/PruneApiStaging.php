<?php

namespace App\Console\Commands;

use App\Models\ApiIdempotencyKey;
use App\Models\ApiUpload;
use Illuminate\Console\Command;

/**
 * Clears what the mobile API leaves behind.
 *
 * Two tables that only ever grow otherwise: idempotency keys, one per write a
 * phone has ever made, and staged photos whose report was never sent — a
 * worker who took three photos and then abandoned the form.
 */
class PruneApiStaging extends Command
{
    protected $signature = 'nadi:prune-api-staging';

    protected $description = 'Hapus kunci idempotensi dan foto staging API yang sudah kedaluwarsa';

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

        $this->info("Kunci idempotensi dihapus: {$keys}");
        $this->info("Foto staging dihapus: {$photos}");

        return self::SUCCESS;
    }
}
