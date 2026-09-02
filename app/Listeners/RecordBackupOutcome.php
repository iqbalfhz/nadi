<?php

namespace App\Listeners;

use App\Settings\BackupSettings;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Throwable;

/**
 * Writes down how the last backup went, so the answer is on screen instead of
 * only in an email nobody receives.
 *
 * spatie/laravel-backup reports failures by mail, and this installation sends
 * mail to the log file — so the nightly backup could stop working and the first
 * anyone would know is when a restore was needed. Reading the true state means
 * listing the Google Drive folder, which took seconds when we did it by hand:
 * far too slow for a page load. Recording the outcome as it happens gives the
 * settings page something instant and honest to show.
 *
 * Methods are named `record*`, not `handle*`: Laravel auto-discovers `handle*`
 * methods in app/Listeners, which would fire each of these twice alongside the
 * explicit subscribe() below. Same reason as LogAccessActivity.
 */
class RecordBackupOutcome
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(BackupWasSuccessful::class, [self::class, 'recordSuccess']);
        $events->listen(BackupHasFailed::class, [self::class, 'recordFailure']);
    }

    public function recordSuccess(BackupWasSuccessful $event): void
    {
        $this->write(true, 'Berhasil.');
    }

    public function recordFailure(BackupHasFailed $event): void
    {
        // The exception text is Google's or mysqldump's own wording. It belongs
        // in the log; the screen gets the first line only, trimmed, so the
        // settings page stays readable.
        Log::error('Backup gagal.', ['exception' => $event->exception->getMessage()]);

        $this->write(false, $this->summarise($event->exception->getMessage()));
    }

    private function write(bool $succeeded, string $message): void
    {
        try {
            $settings = app(BackupSettings::class);
            $settings->last_run_at = now()->toIso8601String();
            $settings->last_run_succeeded = $succeeded;
            $settings->last_run_message = $message;
            $settings->save();
        } catch (Throwable $exception) {
            // Never let bookkeeping turn a successful backup into a failed
            // command, or a failed backup into an unhandled exception that
            // aborts the rest of the scheduled run.
            report($exception);
        }
    }

    private function summarise(string $message): string
    {
        $firstLine = strtok($message, "\n");

        return mb_strimwidth(is_string($firstLine) ? $firstLine : $message, 0, 200, '…');
    }
}
