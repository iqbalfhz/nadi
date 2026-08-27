<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class QueueKioskSettings extends Settings
{
    #[ShouldBeEncrypted]
    public string $pin;

    public bool $is_enabled;

    public static function group(): string
    {
        return 'queue_kiosk';
    }

    /**
     * The value stored in a set-up kiosk's cookie to prove it was unlocked.
     *
     * Keyed with APP_KEY rather than being a bare hash of the PIN: a kiosk
     * PIN is short and numeric, so an unsalted SHA-256 sitting in a cookie
     * can be turned back into the PIN itself in seconds. An HMAC can't be
     * reversed or forged without the server's key.
     *
     * Still derived from the current PIN, so changing or disabling the PIN
     * keeps immediately re-locking every kiosk (see EnsureKioskIsUnlocked).
     */
    public function unlockToken(): string
    {
        return hash_hmac('sha256', $this->pin, (string) config('app.key'));
    }
}
