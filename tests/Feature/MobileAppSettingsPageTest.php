<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageMobileAppSettings;
use App\Settings\MobileAppSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pengaturan → Aplikasi Mobile, saved the way an admin actually saves it.
 *
 * Every field on this page is documented as optional — "kosongkan kalau
 * belum ingin memakai fitur ini". The page crashed with a 500 the first time
 * somebody took it at its word: Filament hands an empty text input over as
 * null, and the settings properties were declared as non-nullable strings.
 * The API tests never noticed, because they wrote the settings object
 * directly and never went through the form.
 */
class MobileAppSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
    }

    /**
     * The exact save that failed in production: both versions filled, the
     * optional download link left empty.
     */
    public function test_the_download_link_can_be_left_empty(): void
    {
        Livewire::test(ManageMobileAppSettings::class)
            ->fillForm([
                'latest_version' => '1.2.1+7',
                'minimum_version' => '1.2.1+7',
                'download_url' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(MobileAppSettings::class)->refresh();

        $this->assertSame('1.2.1+7', $settings->latest_version);
        $this->assertSame([
            'latest_version' => '1.2.1+7',
            'minimum_version' => '1.2.1+7',
            'download_url' => null,
        ], $settings->forApi());
    }

    /**
     * "Kosongkan keduanya kalau belum ingin memakai fitur ini" — so emptying
     * everything has to save, and has to switch the feature off: no `app`
     * block in /me, which is what every build already in the field expects.
     */
    public function test_everything_can_be_cleared_to_switch_the_feature_off(): void
    {
        $this->configure('1.2.1+7', '1.2.0+6', 'https://nadi.example.com/apk');

        Livewire::test(ManageMobileAppSettings::class)
            ->fillForm([
                'latest_version' => '',
                'minimum_version' => '',
                'download_url' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(MobileAppSettings::class)->refresh();

        $this->assertFalse($settings->isConfigured());
        $this->assertNull($settings->forApi());
    }

    public function test_a_minimum_can_be_left_empty_with_a_latest_set(): void
    {
        Livewire::test(ManageMobileAppSettings::class)
            ->fillForm([
                'latest_version' => '1.2.1+7',
                'minimum_version' => '',
                'download_url' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([
            'latest_version' => '1.2.1+7',
            'minimum_version' => null,
            'download_url' => null,
        ], app(MobileAppSettings::class)->refresh()->forApi());
    }

    public function test_a_malformed_version_is_refused(): void
    {
        Livewire::test(ManageMobileAppSettings::class)
            ->fillForm([
                'latest_version' => '1.2.1',
                'minimum_version' => '',
                'download_url' => '',
            ])
            ->call('save')
            ->assertHasFormErrors(['latest_version' => 'regex']);
    }

    private function configure(string $latest, string $minimum, string $downloadUrl): void
    {
        $settings = app(MobileAppSettings::class);
        $settings->latest_version = $latest;
        $settings->minimum_version = $minimum;
        $settings->download_url = $downloadUrl;
        $settings->save();
    }
}
