<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Placeholder default — ganti lewat halaman Pengaturan > Kiosk Antrian
        // di /admin sebelum kiosk dipakai sungguhan. Pre-encrypted here because
        // QueueKioskSettings::$pin is #[ShouldBeEncrypted] — the migrator writes
        // the raw payload directly, bypassing the settings object's own cast.
        $this->migrator->add('queue_kiosk.pin', encrypt('123456'));
        $this->migrator->add('queue_kiosk.is_enabled', true);
    }
};
