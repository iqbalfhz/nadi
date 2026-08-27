<?php

namespace App\Concerns;

/**
 * Records a Pengaturan page save in Riwayat Aktivitas.
 *
 * Settings aren't Eloquent models, so they fire no model events and
 * App\Concerns\LogsNadiActivity can't see them — yet these are among the
 * most sensitive things in the app to change (the kiosk PIN, the Google
 * Drive credentials, whether backups run at all).
 *
 * Only the *names* of the changed settings are recorded, never the values.
 * Every secret on these pages is stored encrypted precisely so it isn't
 * lying around in plaintext, and writing it into an audit table an admin
 * reads would undo that.
 */
trait LogsSettingsChanges
{
    /**
     * @var array<string, mixed>
     */
    protected array $settingsBeforeSave = [];

    /**
     * The beforeSave hook rather than mutateFormDataBeforeSave: pages using
     * this trait may already define the latter for their own reasons (see
     * ManageBackupSettings), and a class method silently wins over a trait's
     * — the snapshot would never be taken and nothing would ever be logged.
     */
    protected function beforeSave(): void
    {
        $this->settingsBeforeSave = app(static::getSettings())->toArray();
    }

    protected function afterSave(): void
    {
        $after = app(static::getSettings())->toArray();

        $changed = [];

        foreach ($after as $key => $value) {
            if (($this->settingsBeforeSave[$key] ?? null) !== $value) {
                $changed[] = $key;
            }
        }

        if ($changed === []) {
            return;
        }

        activity('sistem')
            ->withProperty('pengaturan', static::getSettingsLabel())
            ->withProperty('kolom_diubah', $changed)
            ->log('Ubah '.static::getSettingsLabel());
    }

    /**
     * What the page is called in the sidebar, so the log entry reads the way
     * the admin found it.
     */
    abstract public static function getSettingsLabel(): string;
}
