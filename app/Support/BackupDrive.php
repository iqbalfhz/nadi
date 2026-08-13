<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * spatie/laravel-backup always nests backups under a config('backup.backup.name')
 * subfolder on the destination disk, and checks that path is listable before
 * copying (BackupDestination::isReachable()). masbug/flysystem-google-drive-ext's
 * listContents() throws instead of returning an empty list for a folder that
 * doesn't exist yet, so the very first backup ever run fails before it can
 * create that subfolder itself. Call this once before backup:run to avoid that.
 */
class BackupDrive
{
    public static function ensureBackupFolderExists(): void
    {
        $disk = Storage::disk('google');
        $folder = config('backup.backup.name');

        if (! $disk->directoryExists($folder)) {
            $disk->makeDirectory($folder);
        }
    }
}
