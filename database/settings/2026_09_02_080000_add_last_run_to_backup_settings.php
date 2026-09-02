<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Empty means "never run" — which is exactly the state this project sat
        // in for weeks without anything on screen saying so.
        $this->migrator->add('backup.last_run_at', '');
        $this->migrator->add('backup.last_run_succeeded', false);
        $this->migrator->add('backup.last_run_message', '');
    }
};
