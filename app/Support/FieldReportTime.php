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
     * How far a claim may be off before it is worth recording.
     *
     * Phone clocks drift by seconds as a matter of course, and a report sent
     * straight away from a handset ten seconds fast arrives "in the future".
     * Recording that would put a warning on ordinary reports and teach every
     * supervisor to ignore it. Five minutes is well past any honest drift and
     * well short of a clock somebody set by hand.
     */
    public const CLOCK_TOLERANCE_SECONDS = 300;

    /**
     * A phone's clock is not evidence, so an impossible time is pulled into
     * range rather than refused.
     *
     * Rejecting it would mean a wrong clock loses the report — which defeats
     * the entire reason for letting it be filed offline. Clamped, the worst
     * case is a report timed "now", exactly as if the column did not exist.
     *
     * What was clamped away is not lost: claimed() hands back the original so
     * it can be stored alongside and reviewed.
     */
    public static function clamp(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return self::intoRange(self::parse($value));
    }

    /**
     * What the phone claimed, but only when we did not believe it.
     *
     * Clamping keeps the report — that part is right, and refusing would mean
     * a wrong clock loses it. What was wrong is that clamping happened
     * silently: a handset running three hours fast had its claim replaced
     * with "now" and the row came out looking ordinary.
     *
     * A time in the future is the one clock signal with no honest
     * explanation. No shift pattern, no basement, no outbox delay produces
     * it, which is what makes it worth flagging and a large *backward* gap
     * not — that is simply an outbox coming home.
     *
     * Null when the claim was accepted as sent, or was off by no more than
     * ordinary clock drift — see CLOCK_TOLERANCE_SECONDS.
     */
    public static function claimed(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $claimed = self::parse($value);
        $stored = self::intoRange($claimed);

        $offBy = abs($claimed->getTimestamp() - $stored->getTimestamp());

        return $offBy > self::CLOCK_TOLERANCE_SECONDS ? $claimed : null;
    }

    /**
     * No older than a week, no later than now.
     */
    private static function intoRange(Carbon $time): Carbon
    {
        return $time->copy()
            ->max(now()->subDays(self::MAX_BACKDATE_DAYS))
            ->min(now());
    }

    /**
     * Pulled into the app timezone before anything else, and this is
     * load-bearing. Carbon::parse() keeps whatever zone the string carried,
     * and Eloquent's datetime cast writes a Carbon out in *its own* zone — so
     * a phone sending 16:58Z (23:58 in Jakarta) was stored as 16:58, and a
     * dawn patrol read back as the previous evening. Nothing downstream
     * notices: the column only ever holds wall-clock time, with no zone.
     *
     * The app has sent Z-suffixed times since 1.0.3. Before that it sent no
     * offset at all, which happened to parse as local — and hid this.
     */
    private static function parse(string $value): Carbon
    {
        return Carbon::parse($value)->setTimezone(config('app.timezone'));
    }
}
