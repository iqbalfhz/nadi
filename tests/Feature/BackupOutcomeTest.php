<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageBackupSettings;
use App\Settings\BackupSettings;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Tests\TestCase;

/**
 * spatie/laravel-backup announces failures by email, and this installation
 * sends mail to the log file — so the nightly backup could stop working and the
 * first anyone would know is when a restore was needed.
 *
 * These pin the replacement: the outcome is written down as it happens, and the
 * settings page says so plainly.
 */
class BackupOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): BackupSettings
    {
        $this->app->forgetInstance(BackupSettings::class);

        return app(BackupSettings::class);
    }

    public function test_a_successful_backup_is_recorded(): void
    {
        event(new BackupWasSuccessful('google', 'NADI'));

        $settings = $this->settings();

        $this->assertTrue($settings->last_run_succeeded);
        $this->assertNotSame('', $settings->last_run_at);
    }

    public function test_a_failed_backup_is_recorded_with_a_short_reason(): void
    {
        event(new BackupHasFailed(
            new Exception("TLS/SSL error: self-signed certificate\nbaris kedua yang tidak perlu tampil"),
            'google',
            'NADI',
        ));

        $settings = $this->settings();

        $this->assertFalse($settings->last_run_succeeded);
        $this->assertStringContainsString('self-signed certificate', $settings->last_run_message);
        // Only the first line reaches the screen; the rest lives in the log.
        $this->assertStringNotContainsString('baris kedua', $settings->last_run_message);
    }

    /**
     * Each event must be handled once. Laravel auto-discovers `handle*` methods
     * in app/Listeners, which would double up with the explicit subscribe() —
     * exactly the bug that once wrote every access-log entry twice.
     */
    public function test_the_outcome_is_recorded_once_not_twice(): void
    {
        event(new BackupWasSuccessful('google', 'NADI'));
        $first = $this->settings()->last_run_at;

        event(new BackupHasFailed(new Exception('gagal'), 'google', 'NADI'));

        $settings = $this->settings();

        $this->assertFalse(
            $settings->last_run_succeeded,
            'The later failure must win; a duplicated success listener would overwrite it.',
        );
        $this->assertNotSame('', $first);
    }

    public function test_the_settings_page_reports_when_nothing_has_run_yet(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        Livewire::test(ManageBackupSettings::class)
            ->assertOk()
            ->assertSee('Backup Terakhir')
            ->assertSee('Belum pernah ada backup yang tercatat', false);
    }

    public function test_the_settings_page_reports_a_failure(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        event(new BackupHasFailed(new Exception('mysqldump tidak ditemukan'), 'google', 'NADI'));

        Livewire::test(ManageBackupSettings::class)
            ->assertOk()
            ->assertSee('Gagal pada')
            ->assertSee('mysqldump tidak ditemukan');
    }
}
