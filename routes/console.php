<?php

use App\Settings\BackupSettings;
use App\Support\BackupDrive;
use App\Support\Heartbeat;
use Illuminate\Support\Facades\Schedule;

// Gated on BackupSettings::$enabled (checked fresh on every run) rather than
// only scheduling when configured, so flipping the toggle in Pengaturan >
// Backup Otomatis takes effect immediately without touching the schedule.
Schedule::command('backup:run')
    ->daily()
    ->at('01:00')
    ->when(fn (): bool => app(BackupSettings::class)->enabled)
    // Swallow failures here on purpose: if credentials are bad, let
    // backup:run itself fail and send its normal failure notification,
    // instead of an uncaught exception aborting this schedule:run tick
    // (and every other command still due in it) before that can happen.
    ->before(function (): void {
        try {
            BackupDrive::ensureBackupFolderExists();
        } catch (Throwable) {
            // no-op — see comment above
        }
    })
    ->onOneServer();

Schedule::command('backup:clean')
    ->daily()
    ->at('02:00')
    ->when(fn (): bool => app(BackupSettings::class)->enabled)
    ->onOneServer();

Schedule::command('backup:monitor')
    ->daily()
    ->at('03:00')
    ->when(fn (): bool => app(BackupSettings::class)->enabled)
    ->onOneServer();

// Retention for Riwayat Aktivitas — 365 days, set in config/activitylog.php.
// Without this the log is the one table in NADI that only ever grows.
Schedule::command('activitylog:clean')
    ->daily()
    ->at('04:00')
    ->onOneServer();

// What the mobile API leaves behind: spent idempotency keys, and photos a
// worker uploaded for a report they never sent.
Schedule::command('nadi:prune-api-staging')
    ->daily()
    ->at('04:30')
    ->onOneServer();

// Proof the scheduler itself is alive — the container healthcheck reads it.
// Every job above depends on schedule:work, and a wedged one fails silently:
// no crash, no restart, just a backup that quietly never runs again. See
// App\Support\Heartbeat. Not onOneServer(): it has to run in *this*
// container, because the file it writes is only visible here.
Schedule::call(function (): void {
    Heartbeat::beat(Heartbeat::SCHEDULER);
})
    ->everyMinute()
    ->name('heartbeat')
    ->description('Tanda hidup scheduler untuk healthcheck container');

// Which patrols deserve a second look. Runs after the night shift has ended
// and its outbox has had time to come home — flags depend on a scan's
// neighbours, and a basement report can arrive hours after the one it makes
// impossible. Nothing here refuses a report; it only marks what to review.
Schedule::command('nadi:assess-patrols')
    ->daily()
    ->at('05:00')
    ->onOneServer();
