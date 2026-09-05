<?php

namespace App\Console\Commands;

use App\Models\HkInspection;
use App\Models\ObChecklist;
use App\Models\SecurityPatrol;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Repairs field reports whose submitted_at was stored seven hours early.
 *
 * Between the app switching to Z-suffixed times (1.0.3) and the fix in
 * FieldReportTime::clamp(), a zone-tagged time was written out in its own
 * zone rather than Jakarta's — so 23:46 WIB, sent as 16:46Z, was stored as
 * 16:46. A dawn round read back as the previous evening.
 *
 * Deliberately not automatic, and deliberately not a migration. Arithmetic
 * alone cannot tell a shifted report from one genuinely held in an outbox for
 * seven hours — an overnight round flushed when the guard came up from the
 * basement looks identical. So this lists what it would change and stops,
 * and only writes when told to.
 */
class FixSubmittedAtOffset extends Command
{
    protected $signature = 'nadi:fix-submitted-at-offset
        {--terapkan : Tulis perubahannya. Tanpa ini, hanya menampilkan daftarnya}
        {--sejak=2026-09-05 : Batas awal, berdasarkan waktu terima server}
        {--sampai=2026-09-07 : Batas akhir, berdasarkan waktu terima server}';

    protected $description = 'Perbaiki submitted_at yang tersimpan tujuh jam lebih awal (laporan sebelum 6 September 2026)';

    /**
     * The shift the bug introduced: the app timezone's offset from UTC.
     */
    private const HOURS = 7;

    /**
     * Below this, the gap is an ordinary outbox delay and nothing to do with
     * the bug. Just under seven hours, so a report that also queued for a
     * minute or two on top of the shift is still caught.
     */
    private const SUSPECT_FROM_MINUTES = 6 * 60 + 50;

    /**
     * @var array<string, class-string<ObChecklist|SecurityPatrol|HkInspection>>
     */
    private const MODELS = [
        'Checklist OB' => ObChecklist::class,
        'Patroli Security' => SecurityPatrol::class,
        'Inspeksi HK' => HkInspection::class,
    ];

    public function handle(): int
    {
        $sejak = Carbon::parse((string) $this->option('sejak'))->startOfDay();
        $sampai = Carbon::parse((string) $this->option('sampai'))->endOfDay();
        $terapkan = (bool) $this->option('terapkan');

        $this->info("Rentang waktu terima server: {$sejak->format('d M Y H:i')} — {$sampai->format('d M Y H:i')}");
        $this->newLine();

        $total = 0;

        foreach (self::MODELS as $label => $model) {
            $rows = $this->candidates($model, $sejak, $sampai);

            if ($rows === []) {
                $this->line("{$label}: tidak ada yang mencurigakan.");

                continue;
            }

            $jumlah = count($rows);

            $this->warn("{$label}: {$jumlah} laporan");

            $this->table(
                ['ID', 'Tersimpan', 'Jadi', 'Diterima server', 'Selisih setelah diperbaiki'],
                array_map(fn (ObChecklist|SecurityPatrol|HkInspection $row): array => [
                    $row->getKey(),
                    $row->submitted_at?->format('d M H:i'),
                    $this->corrected($row)->format('d M H:i'),
                    $row->created_at?->format('d M H:i'),
                    $this->gap($row),
                ], $rows),
            );

            if ($terapkan) {
                foreach ($rows as $row) {
                    // Written straight to the column. Going through the model
                    // would fire the activity log and stamp updated_at, and a
                    // clock repair is not an edit anybody made.
                    $row->newQuery()->whereKey($row->getKey())->update([
                        'submitted_at' => $this->corrected($row),
                    ]);
                }

                $this->info("  → {$jumlah} baris diperbaiki.");
            }

            $total += $jumlah;
        }

        $this->newLine();

        if ($total === 0) {
            $this->info('Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        if (! $terapkan) {
            $this->warn("Belum ada yang diubah. {$total} laporan di atas akan digeser +".self::HOURS.' jam.');
            $this->line('Periksa kolom "Jadi" — kalau jamnya masuk akal untuk shift petugasnya, jalankan ulang dengan --terapkan.');

            return self::SUCCESS;
        }

        $this->info("Selesai. {$total} laporan diperbaiki.");

        return self::SUCCESS;
    }

    /**
     * Filtered in PHP rather than SQL on purpose: the datetime arithmetic
     * differs between MySQL (production) and SQLite (local, tests), and the
     * window is a day or two of reports — small enough that loading it costs
     * nothing and portability is worth more than the WHERE clause.
     *
     * @param  class-string<ObChecklist|SecurityPatrol|HkInspection>  $model
     * @return array<int, ObChecklist|SecurityPatrol|HkInspection>
     */
    private function candidates(string $model, Carbon $sejak, Carbon $sampai): array
    {
        return $model::query()
            ->whereNotNull('submitted_at')
            ->whereBetween('created_at', [$sejak, $sampai])
            ->orderBy('created_at')
            ->get()
            // The shift always leaves submitted_at at least seven hours older
            // than the arrival. Anything closer never carried it.
            ->filter(fn (ObChecklist|SecurityPatrol|HkInspection $row): bool => $row->submitted_at !== null
                && $row->created_at !== null
                && $row->submitted_at->diffInMinutes($row->created_at) >= self::SUSPECT_FROM_MINUTES)
            ->values()
            ->all();
    }

    private function corrected(ObChecklist|SecurityPatrol|HkInspection $row): Carbon
    {
        return Carbon::instance($row->submitted_at->toDateTime())->addHours(self::HOURS);
    }

    private function gap(ObChecklist|SecurityPatrol|HkInspection $row): string
    {
        $minutes = (int) $this->corrected($row)->diffInMinutes($row->created_at);

        return $minutes < 60
            ? "{$minutes} menit"
            : intdiv($minutes, 60).' jam '.($minutes % 60).' menit';
    }
}
