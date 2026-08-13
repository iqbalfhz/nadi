<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageBackupSettings;
use App\Notifications\BackupNotifiable;
use App\Settings\BackupSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Tests\TestCase;

class BackupSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_saving_the_form_persists_settings_including_encrypted_fields(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ManageBackupSettings::class)
            ->fillForm([
                'enabled' => true,
                'notify_email' => 'iqbalfahrozi284@gmail.com',
                'client_id' => 'client-id.apps.googleusercontent.com',
                'client_secret' => 'super-secret',
                'refresh_token' => 'refresh-token-value',
                'folder' => 'NADI Backups',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(BackupSettings::class);

        $this->assertTrue($settings->enabled);
        $this->assertSame('iqbalfahrozi284@gmail.com', $settings->notify_email);
        $this->assertSame('client-id.apps.googleusercontent.com', $settings->client_id);
        $this->assertSame('super-secret', $settings->client_secret);
        $this->assertSame('refresh-token-value', $settings->refresh_token);
        $this->assertSame('NADI Backups', $settings->folder);
    }

    public function test_enabling_backup_requires_google_credentials(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ManageBackupSettings::class)
            ->fillForm([
                'enabled' => true,
                'notify_email' => '',
                'client_id' => '',
                'client_secret' => '',
                'refresh_token' => '',
            ])
            ->call('save')
            ->assertHasFormErrors(['notify_email', 'client_id', 'client_secret', 'refresh_token']);
    }

    public function test_leaving_backup_disabled_does_not_require_google_credentials(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ManageBackupSettings::class)
            ->fillForm([
                'enabled' => false,
                'notify_email' => '',
                'client_id' => '',
                'client_secret' => '',
                'refresh_token' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_backup_notifications_route_to_the_configured_email(): void
    {
        $settings = app(BackupSettings::class);
        $settings->notify_email = 'ops@tangcity.com';
        $settings->save();

        $notifiable = new BackupNotifiable;

        $this->assertSame('ops@tangcity.com', $notifiable->routeNotificationForMail());
    }

    public function test_the_database_dump_is_scoped_to_only_the_document_numbering_tables(): void
    {
        $dumper = DbDumperFactory::createFromConnection(config('database.default'));

        $includeTables = (new \ReflectionProperty($dumper, 'includeTables'))->getValue($dumper);

        $this->assertSame(
            ['document_types', 'companies', 'departments', 'documents'],
            $includeTables,
        );
    }

    public function test_no_files_are_included_in_the_backup(): void
    {
        $this->assertSame([], config('backup.backup.source.files.include'));
    }
}
