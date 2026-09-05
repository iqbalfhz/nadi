<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One distinct failure of the mobile app, in one released version.
 *
 * See the migration for why this table exists at all. This class holds the
 * one piece of logic that matters: the same crash arriving again updates the
 * row it already has instead of adding another.
 *
 * @property int $id
 * @property string $fingerprint
 * @property string|null $app_version
 * @property string $message
 * @property string|null $stack
 * @property string|null $platform
 * @property string|null $device
 * @property string|null $os_version
 * @property int|null $user_id
 * @property int $occurrences
 * @property CarbonInterface $first_occurred_at
 * @property CarbonInterface $last_occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'fingerprint',
    'app_version',
    'message',
    'stack',
    'platform',
    'device',
    'os_version',
    'user_id',
    'first_occurred_at',
    'last_occurred_at',
])]
class AppCrashReport extends Model
{
    /**
     * How much of the stack takes part in the fingerprint.
     *
     * Enough to tell two different bugs apart, few enough that the same bug
     * still groups when the frames below it differ — a widget rebuilt from a
     * different parent is the same bug.
     */
    private const FINGERPRINT_FRAMES = 5;

    /**
     * How long a crash group is kept. Long enough to still be there when
     * somebody finally has time to look, short enough that the table does not
     * become another one that only ever grows.
     */
    public const RETENTION_DAYS = 180;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_occurred_at' => 'datetime',
            'last_occurred_at' => 'datetime',
            'occurrences' => 'integer',
        ];
    }

    /**
     * Record one failure, merging it into its group.
     *
     * @param  array{message: string, stack: string|null, app_version: string|null, platform: string|null, device: string|null, os_version: string|null}  $crash
     */
    public static function record(array $crash, CarbonInterface $occurredAt, ?int $userId): self
    {
        $fingerprint = self::fingerprint($crash['message'], $crash['stack']);

        $report = self::query()->firstOrNew([
            'fingerprint' => $fingerprint,
            'app_version' => $crash['app_version'],
        ]);

        if ($report->exists) {
            $report->occurrences++;

            // Kept as the earliest and latest *claimed* times, not simply
            // overwritten: a crash queued on a handset for two days can
            // arrive after one that happened this morning.
            $report->first_occurred_at = $occurredAt->min($report->first_occurred_at);
            $report->last_occurred_at = $occurredAt->max($report->last_occurred_at);
        } else {
            $report->fill([
                'message' => $crash['message'],
                'stack' => $crash['stack'],
                'first_occurred_at' => $occurredAt,
                'last_occurred_at' => $occurredAt,
            ]);
        }

        // Always the latest reporter's device, so the row describes the most
        // recent occurrence rather than the first one somebody happened to
        // hit months ago.
        $report->fill([
            'platform' => $crash['platform'],
            'device' => $crash['device'],
            'os_version' => $crash['os_version'],
            'user_id' => $userId,
        ]);

        $report->save();

        return $report;
    }

    /**
     * What counts as "the same crash".
     *
     * The message alone is too coarse — half of Flutter's failures say "Null
     * check operator used on a null value" — and the whole stack is too fine,
     * because the frames below the fault differ by however the screen was
     * reached.
     */
    public static function fingerprint(string $message, ?string $stack): string
    {
        $frames = collect(preg_split('/\R/', (string) $stack) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->take(self::FINGERPRINT_FRAMES)
            // Memory addresses and closure counters differ on every run and
            // would otherwise make each occurrence its own "distinct" crash.
            ->map(fn (string $line): string => (string) preg_replace('/0x[0-9a-f]+/i', '0x', $line))
            ->implode("\n");

        return hash('sha256', trim($message)."\n".$frames);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
