<?php

namespace App\Livewire\Queue;

use App\Settings\QueueKioskSettings;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class KioskPinGate extends Component
{
    use WithRateLimiting;

    public string $pin = '';

    #[Computed]
    public function isEnabled(): bool
    {
        return app(QueueKioskSettings::class)->is_enabled;
    }

    public function verify(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('pin', "Terlalu banyak percobaan. Coba lagi dalam {$exception->secondsUntilAvailable} detik.");

            return;
        }

        $settings = app(QueueKioskSettings::class);

        if (! $settings->is_enabled) {
            $this->addError('pin', 'Kiosk sedang dinonaktifkan oleh admin.');

            return;
        }

        // hash_equals, not !==: a PIN is a secret, and a byte-by-byte
        // comparison leaks how much of a guess was right through timing.
        if ($this->pin === '' || ! hash_equals($settings->pin, $this->pin)) {
            $this->addError('pin', 'PIN salah.');

            return;
        }

        // Kiosk device is being set up once — remember it for years, not just
        // this session, since staff won't touch this screen again after setup.
        // The token is derived from the current PIN, so a future PIN change or
        // disable (see EnsureKioskIsUnlocked) immediately re-locks this device.
        // Positional, not named: CookieJar::queue() forwards through
        // array_values(), which strips argument names and would silently
        // shift every value one slot along (secure landing in $path, and so
        // on). Order is make($name, $value, $minutes, $path, $domain,
        // $secure, $httpOnly, $raw, $sameSite).
        cookie()->queue(
            'queue_kiosk_unlocked',
            $settings->unlockToken(),
            60 * 24 * 365 * 5,
            null,
            null,
            app()->isProduction(),
            true,
            false,
            'lax',
        );

        $this->redirect(route('queue.kiosk.take'));
    }

    public function render(): View
    {
        return view('livewire.queue.kiosk-pin-gate')
            ->layout('layouts.queue');
    }
}
