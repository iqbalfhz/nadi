<?php

namespace App\Console\Commands;

use App\Models\SecurityPatrol;
use App\Support\PatrolReview;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Recomputes which patrols are worth a second look.
 *
 * Runs over a window rather than only over new rows, because a patrol's
 * flags depend on its neighbours and neighbours arrive late: a scan filed in
 * a basement at 03:15 can reach the server after the 04:00 one, and only
 * then reveal that the pair is impossible. Re-running is safe — it writes
 * only where the answer changed.
 */
class AssessPatrols extends Command
{
    protected $signature = 'nadi:assess-patrols
        {--hari=14 : Berapa hari ke belakang yang dinilai ulang}';

    protected $description = 'Tandai patroli yang perlu ditinjau (tidak pernah menolak laporan)';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('hari'));
        $since = Carbon::now()->subDays($days)->startOfDay();

        $patrols = SecurityPatrol::query()
            // On the arrival time, not the claimed one: this decides which
            // rows to load, and a report that came home late still needs
            // assessing however old its claim is.
            ->where('created_at', '>=', $since)
            ->get();

        if ($patrols->isEmpty()) {
            $this->info('Tidak ada patroli dalam rentang itu.');

            return self::SUCCESS;
        }

        $changed = PatrolReview::assess($patrols);
        $flagged = SecurityPatrol::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('review_flags')
            ->count();

        $this->info("Patroli dinilai: {$patrols->count()}");
        $this->info("Penandaan berubah: {$changed}");

        if ($flagged > 0) {
            $this->warn("Perlu ditinjau: {$flagged} — buka /admin → Security → Riwayat Patroli, filter \"Perlu ditinjau\".");
        }

        return self::SUCCESS;
    }
}
