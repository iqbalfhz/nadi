<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A readable answer to "are queued jobs actually being processed?".
 *
 * This exists because of a real incident: the queue worker container was in a
 * crash loop, so Checklist HK reports were saved, showed green on screen, and
 * silently never reached Telegram. Nothing in the application said a word — the
 * only way to find out was reading container logs in Coolify.
 *
 * Counts every pending job rather than only the Telegram ones. A stuck queue is
 * stuck for everything, and narrowing the count would understate the problem.
 */
class QueueHealth
{
    /**
     * How long a job may sit before it counts as stuck. A healthy worker takes
     * one within a second; the grace covers the gap while the worker recycles
     * itself (queue:work --max-time) and while a deploy swaps containers.
     */
    private const STALL_SECONDS = 120;

    private function __construct(
        public readonly bool $readable,
        public readonly int $pending,
        public readonly int $failed,
        public readonly ?Carbon $oldestPendingAt,
    ) {}

    public static function read(): self
    {
        // Only the database driver keeps the queue somewhere countable without
        // opening a connection to Redis or a remote broker. NADI runs on the
        // database driver; if that ever changes, the page says so plainly
        // rather than showing numbers it cannot stand behind.
        if (config('queue.default') !== 'database' || ! Schema::hasTable('jobs')) {
            return new self(false, 0, 0, null);
        }

        $oldest = DB::table('jobs')->min('created_at');

        return new self(
            readable: true,
            pending: DB::table('jobs')->count(),
            failed: self::failedCount(),
            // jobs.created_at is a unix timestamp column, not a datetime.
            oldestPendingAt: $oldest === null ? null : Carbon::createFromTimestamp((int) $oldest),
        );
    }

    private static function failedCount(): int
    {
        $table = config('queue.failed.table', 'failed_jobs');

        if (! is_string($table) || ! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->count();
    }

    /**
     * Work is waiting and has been waiting too long to blame ordinary latency.
     */
    public function isStalled(): bool
    {
        return $this->oldestPendingAt !== null
            && $this->oldestPendingAt->diffInSeconds(now(), absolute: true) > self::STALL_SECONDS;
    }

    public function summary(): string
    {
        if (! $this->readable) {
            return 'Status antrean tidak bisa dibaca karena antrean tidak disimpan di database.';
        }

        if ($this->failed > 0) {
            return "{$this->failed} pekerjaan gagal diproses setelah beberapa kali percobaan. "
                .'Laporannya tetap tersimpan — hanya pengirimannya yang belum berhasil.';
        }

        if ($this->isStalled()) {
            $since = $this->oldestPendingAt?->diffForHumans() ?? '';

            return "{$this->pending} pekerjaan tertahan, yang tertua sejak {$since}. "
                .'Biasanya ini berarti pekerja antrean (container "queue") sedang tidak berjalan.';
        }

        if ($this->pending > 0) {
            return "{$this->pending} pekerjaan sedang menunggu giliran. Ini normal, tunggu sebentar lalu muat ulang halaman.";
        }

        return 'Antrean bersih — semua laporan sudah terkirim.';
    }

    public function color(): string
    {
        return match (true) {
            ! $this->readable => 'gray',
            $this->failed > 0 => 'danger',
            $this->isStalled() => 'warning',
            $this->pending > 0 => 'info',
            default => 'success',
        };
    }
}
