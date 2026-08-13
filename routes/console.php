<?php

use App\Settings\BackupSettings;
use App\Support\BackupDrive;
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
