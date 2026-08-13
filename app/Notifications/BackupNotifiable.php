<?php

namespace App\Notifications;

use App\Settings\BackupSettings;
use Spatie\Backup\Notifications\Notifiable;

/**
 * Routes backup success/failure notifications to whatever address is set in
 * Pengaturan > Backup Otomatis, instead of the static config('backup.*.mail.to')
 * value — read fresh at send time so it stays correct even under config:cache.
 */
class BackupNotifiable extends Notifiable
{
    public function routeNotificationForMail(): string
    {
        return app(BackupSettings::class)->notify_email;
    }
}
