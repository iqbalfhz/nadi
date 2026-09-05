<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * When the worker says they did the job, for reports filed offline.
 *
 * A report written at 09:14 in a basement and flushed at 11:03 when signal
 * returns would otherwise carry only created_at 11:03 — and a supervisor
 * reading "twelve reports, all at 11:03" learns nothing from the times.
 */
class FieldReportTime
{
    /**
     * How far back a phone may date a report. Anything older is a device with
     * a wrong clock, not a genuinely week-old memory.
     */
    public const MAX_BACKDATE_DAYS = 7;

    /**
     * A phone's clock is not evidence, so an impossible time is pulled into
     * range rather than refused.
     *
     * Rejecting it would mean a wrong clock loses the report — which defeats
     * the entire reason for letting it be filed offline. Clamped, the worst
     * case is a report timed "now", exactly as if the column did not exist.
     */
    public static function clamp(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        // Pulled into the app timezone before anything else, and this line is
        // load-bearing. Carbon::parse() keeps whatever zone the string carried,
        // and Eloquent's datetime cast writes a Carbon out in *its own* zone —
        // so a phone sending 16:58Z (23:58 in Jakarta) was stored as 16:58, and a
        // dawn patrol read back as the previous evening. Nothing downstream
        // notices: the column only ever holds wall-clock time, with no zone.
        //
        // The app has sent Z-suffixed times since 1.0.3. Before that it sent no
        // offset at all, which happened to parse as local — and hid this.
        return Carbon::parse($value)
            ->setTimezone(config('app.timezone'))
            ->max(now()->subDays(self::MAX_BACKDATE_DAYS))
            ->min(now());
    }
}
