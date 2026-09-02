<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class BackupSettings extends Settings
{
    public bool $enabled;

    public string $notify_email;

    public string $client_id;

    #[ShouldBeEncrypted]
    public string $client_secret;

    #[ShouldBeEncrypted]
    public string $refresh_token;

    public string $folder;

    /**
     * Outcome of the most recent backup, recorded by
     * App\Listeners\RecordBackupOutcome.
     *
     * These exist because spatie/laravel-backup only reports failures by email,
     * and this installation sends mail to the log file — so a backup that
     * stopped working would have told nobody. Reading the real answer means
     * listing the Google Drive folder, which is far too slow to do on a page
     * load, so the result is written down as it happens instead.
     */
    public string $last_run_at;

    public bool $last_run_succeeded;

    public string $last_run_message;

    public static function group(): string
    {
        return 'backup';
    }
}
