<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Left empty on purpose. While latest_version is blank the /me response
     * omits the `app` block entirely and every handset already in the field
     * behaves exactly as it did before — no banner, no block.
     *
     * Filled in via Pengaturan > Aplikasi Mobile in /admin, on release day.
     */
    public function up(): void
    {
        $this->migrator->add('mobile_app.latest_version', '');
        $this->migrator->add('mobile_app.minimum_version', '');
        $this->migrator->add('mobile_app.download_url', '');
    }
};
