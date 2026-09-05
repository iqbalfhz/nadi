<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Which build of the Flutter app the handsets should be running.
 *
 * The APK is handed to officers directly, not through Play Store, so nothing
 * updates itself and nothing tells anyone their copy is stale. An officer can
 * carry a three-month-old build without ever knowing — and the fix that was
 * released the same afternoon never reaches the person who needed it. That is
 * not hypothetical: the QR scanning bug that broke the Security module
 * outright was fixed within hours, and twenty handsets in the field would all
 * have kept running the broken build until each was phoned individually.
 *
 * Settings rather than a table or .env: this is a value an admin changes on
 * release day, and it has to be changeable without a deploy.
 */
class MobileAppSettings extends Settings
{
    /**
     * Newest released build. A handset below it shows a "new version
     * available" banner and carries on working.
     */
    public string $latest_version;

    /**
     * Oldest build still allowed to run. A handset below it is blocked until
     * it updates.
     *
     * Raise this rarely and deliberately. An officer blocked mid-shift cannot
     * file anything at all, and whatever is already queued in their outbox is
     * held with them until they find a way to install the new APK.
     */
    public string $minimum_version;

    /**
     * Where to get the new build. Optional — without it the banner can only
     * say that an update exists, which leaves the officer to work out who to
     * ask.
     */
    public string $download_url;

    public static function group(): string
    {
        return 'mobile_app';
    }

    /**
     * Whether there is anything worth telling the app.
     *
     * With no version configured the `app` block is left out of /me entirely,
     * and the handsets behave exactly as they did before this existed — no
     * banner, no block. That is what makes this safe to switch on at any time
     * without breaking the builds already in the field.
     */
    public function isConfigured(): bool
    {
        return filled($this->latest_version);
    }

    /**
     * What /me should carry, or null when nothing is configured.
     *
     * @return array{latest_version: string, minimum_version: string|null, download_url: string|null}|null
     */
    public function forApi(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return [
            'latest_version' => $this->latest_version,
            'minimum_version' => filled($this->minimum_version) ? $this->minimum_version : null,
            'download_url' => filled($this->download_url) ? $this->download_url : null,
        ];
    }
}
