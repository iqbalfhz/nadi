<?php

namespace App\Support;

use App\Enums\PatrolReviewFlag;
use App\Models\SecurityPatrol;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Works out which patrols deserve a second look.
 *
 * A single scan always looks legitimate — that is the whole difficulty. The
 * QR code on a post is static, so a guard who photographs the sticker once
 * can scan that photo from anywhere, and no single report can tell the
 * difference. What gives it away is the pattern across reports, and the
 * server is the only place that holds all of them.
 *
 * Three rules, no more. Two signals the issue asked for are deliberately not
 * implemented, and saying why matters as much as what is here:
 *
 * - **"A full round finished much faster than usual"** needs a definition of
 *   a round. NADI has no such thing — patrols are individual scans with no
 *   grouping, no expected sequence and no schedule. Inventing one from time
 *   gaps would mostly measure how the guard was told to walk that night.
 * - **"Times spaced too evenly"** is real but noisy: a guard who genuinely
 *   walks a fixed route at a steady pace produces exactly that shape. It
 *   would flag the most disciplined people on the payroll.
 *
 * What is here instead is narrow and hard to argue with, which is what makes
 * a flag worth reading.
 */
class PatrolReview
{
    /**
     * Two different posts closer together than this is nobody walking.
     *
     * Generous on purpose. Posts have no coordinates in NADI, so this cannot
     * ask "is that far enough to matter" — the threshold has to be short
     * enough that no pair of posts in one building could be in dispute.
     */
    public const IMPOSSIBLE_TRAVEL_SECONDS = 90;

    /**
     * The same post again inside this window is a repeat, not a round.
     */
    public const REPEAT_WINDOW_MINUTES = 10;

    /**
     * Recompute the flags for a set of patrols.
     *
     * Takes the whole set rather than one row because every rule here is
     * about a patrol's neighbours, and reports arrive late and out of order:
     * a scan filed in a basement at 03:15 can land after the 04:00 one, and
     * only then reveal that the pair is impossible.
     *
     * @param  Collection<int, SecurityPatrol>  $patrols
     * @return int how many rows changed
     */
    public static function assess(Collection $patrols): int
    {
        $changed = 0;

        // Grouped per guard: every rule compares one person's own scans.
        // Two guards at the same post a minute apart is a shift handover.
        foreach ($patrols->groupBy('user_id') as $ofOneGuard) {
            $ordered = $ofOneGuard
                ->sortBy(fn (SecurityPatrol $patrol) => self::happenedAt($patrol))
                ->values();

            foreach ($ordered as $index => $patrol) {
                $flags = self::flagsFor($patrol, $ordered->get($index - 1));

                if (self::store($patrol, $flags)) {
                    $changed++;
                }
            }
        }

        return $changed;
    }

    /**
     * @return array<int, string>
     */
    private static function flagsFor(SecurityPatrol $patrol, ?SecurityPatrol $previous): array
    {
        $flags = [];

        // Independent of the neighbours: the handset said the scan happened
        // at a time that had not arrived yet.
        if ($patrol->submitted_at_claimed !== null && $patrol->submitted_at !== null
            && $patrol->submitted_at_claimed->greaterThan($patrol->submitted_at)) {
            $flags[] = PatrolReviewFlag::ClockAhead->value;
        }

        if ($previous === null) {
            return $flags;
        }

        $seconds = self::happenedAt($previous)->diffInSeconds(self::happenedAt($patrol));

        if ($previous->security_checkpoint_id === $patrol->security_checkpoint_id) {
            if ($seconds <= self::REPEAT_WINDOW_MINUTES * 60) {
                $flags[] = PatrolReviewFlag::RepeatedCheckpoint->value;
            }

            return $flags;
        }

        if ($seconds <= self::IMPOSSIBLE_TRAVEL_SECONDS) {
            $flags[] = PatrolReviewFlag::ImpossibleTravel->value;
        }

        return $flags;
    }

    /**
     * When the guard says they were there, falling back to when the server
     * heard about it.
     *
     * It has to be the claimed time: ordering by arrival would put a whole
     * outbox in the order the signal came back, and then every report in it
     * would look like impossible travel.
     */
    private static function happenedAt(SecurityPatrol $patrol): CarbonInterface
    {
        return $patrol->submitted_at ?? $patrol->created_at;
    }

    /**
     * @param  array<int, string>  $flags
     */
    private static function store(SecurityPatrol $patrol, array $flags): bool
    {
        $new = $flags === [] ? null : array_values(array_unique($flags));

        if ($new === $patrol->review_flags) {
            return false;
        }

        // Straight to the column: a recomputation is not an edit anybody
        // made, and it must not fill Riwayat Aktivitas or move updated_at.
        $patrol->newQuery()->whereKey($patrol->getKey())->update(['review_flags' => $new]);

        return true;
    }
}
