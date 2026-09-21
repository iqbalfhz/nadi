<?php

namespace App\Support;

/**
 * Proof that a long-running process is still doing its job.
 *
 * `scheduler` and `queue` have no port to probe, and a process that dies
 * takes its container down with it — so restart already covers a crash. What
 * it does not cover is a process that is alive but stuck: schedule:work
 * wedged, and the nightly backup, the patrol assessment and every clean-up
 * silently stop. The container stays up, nothing restarts, and nobody finds
 * out until the backup they need is not there.
 *
 * So each process touches a file while it is working, and docker-compose.yml
 * checks the file's age. The check there is plain `php -r` rather than an
 * artisan command, so a healthcheck every minute does not boot the framework.
 *
 * The paths are the contract with docker-compose.yml. HeartbeatTest reads the
 * compose file and fails if the two drift apart.
 */
class Heartbeat
{
    public const SCHEDULER = 'scheduler';

    public const QUEUE = 'queue';

    /**
     * The queue worker fires its hook every loop — every three seconds while
     * idle. Touching the file each time would be a write every few seconds
     * for nothing; the healthcheck only needs to know about minutes.
     */
    private const MIN_INTERVAL_SECONDS = 30;

    /**
     * Mark the process as alive.
     *
     * Never throws and never returns a value, both deliberately. It runs
     * inside the queue worker's loop, where an exception would kill the
     * worker over a file that exists only for monitoring — and where a
     * listener returning `false` makes the worker stop picking up jobs.
     * A heartbeat that cannot be written simply goes stale, which is exactly
     * what the healthcheck is there to notice.
     */
    public static function beat(string $name): void
    {
        $path = self::path($name);
        $now = now()->getTimestamp();

        // Long-running processes cache stat() results; without this the
        // worker would keep reading the mtime from its first check forever.
        clearstatcache(true, $path);
        $last = @filemtime($path);

        if ($last !== false && $now - $last < self::MIN_INTERVAL_SECONDS) {
            return;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        @touch($path, $now);
    }

    public static function path(string $name): string
    {
        return storage_path('framework/heartbeats/'.$name);
    }
}
