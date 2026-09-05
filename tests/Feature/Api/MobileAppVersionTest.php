<?php

namespace Tests\Feature\Api;

use App\Settings\MobileAppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Telling the app which build it should be running.
 *
 * The APK is handed to officers directly, so nothing updates itself and
 * nothing tells anyone their copy is stale. /me already runs on every launch,
 * so this rides along there rather than costing a request of its own.
 */
class MobileAppVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_carries_the_released_version(): void
    {
        $this->configure('1.0.3+4', '1.0.0+1', 'https://nadi.example.com/apk');
        $this->actingAsMobileUser(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.app.latest_version', '1.0.3+4')
            ->assertJsonPath('data.app.minimum_version', '1.0.0+1')
            ->assertJsonPath('data.app.download_url', 'https://nadi.example.com/apk');
    }

    /**
     * The whole reason this is safe to add at all: with nothing configured the
     * block is absent, and an absent block is what every build already in the
     * field expects. No banner, no block, no change.
     */
    public function test_nothing_is_sent_while_no_version_is_configured(): void
    {
        $this->actingAsMobileUser(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonMissingPath('data.app');
    }

    /**
     * A minimum nobody has set must not read as "block everything". Absent is
     * the safe answer, and the app treats it as no floor at all.
     */
    public function test_an_unset_minimum_is_null_rather_than_empty(): void
    {
        $this->configure('1.0.3+4');
        $this->actingAsMobileUser(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.app.latest_version', '1.0.3+4')
            ->assertJsonPath('data.app.minimum_version', null)
            ->assertJsonPath('data.app.download_url', null);
    }

    /**
     * Adding the block must not disturb what the app already reads from /me.
     */
    public function test_the_rest_of_me_is_unchanged(): void
    {
        $this->configure('1.0.3+4', '1.0.0+1');
        $user = $this->actingAsMobileUser(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.modules', ['ob']);
    }

    private function configure(string $latest, string $minimum = '', string $downloadUrl = ''): void
    {
        $settings = app(MobileAppSettings::class);
        $settings->latest_version = $latest;
        $settings->minimum_version = $minimum;
        $settings->download_url = $downloadUrl;
        $settings->save();
    }
}
