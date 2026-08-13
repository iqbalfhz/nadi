<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Left disabled/empty by default — filled in via Pengaturan > Backup
        // Otomatis in /admin. client_secret/refresh_token are pre-encrypted
        // here because BackupSettings marks them #[ShouldBeEncrypted]; the
        // migrator writes the raw payload directly, bypassing the settings
        // object's own cast.
        $this->migrator->add('backup.enabled', false);
        $this->migrator->add('backup.notify_email', '');
        $this->migrator->add('backup.client_id', '');
        $this->migrator->add('backup.client_secret', encrypt(''));
        $this->migrator->add('backup.refresh_token', encrypt(''));
        $this->migrator->add('backup.folder', 'NADI Backups');
    }
};
