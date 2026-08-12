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

        if ($this->pin === '' || $this->pin !== $settings->pin) {
            $this->addError('pin', 'PIN salah.');

            return;
        }

        // Kiosk device is being set up once — remember it for years, not just
        // this session, since staff won't touch this screen again after setup.
        // Hashed against the current PIN so a future PIN change or disable
        // (see EnsureKioskIsUnlocked) immediately re-locks this device too.
        cookie()->queue('queue_kiosk_unlocked', hash('sha256', $settings->pin), 60 * 24 * 365 * 5);

        $this->redirect(route('queue.kiosk.take'));
    }

    public function render(): View
    {
        return view('livewire.queue.kiosk-pin-gate')
            ->layout('layouts.queue');
    }
}
