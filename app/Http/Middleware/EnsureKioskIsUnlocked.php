<?php

namespace App\Http\Middleware;

use App\Settings\QueueKioskSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKioskIsUnlocked
{
    /**
     * The public kiosk is reachable from anywhere on the internet, not just
     * the physical tablet in the office, and visitors don't have accounts to
     * log in with. This checks for a cookie hashed against the *current*
     * admin-set PIN (see KioskPinGate / ManageQueueKioskSettings) — rotating
     * the PIN or disabling the kiosk immediately invalidates every kiosk
     * that was previously unlocked, without needing to track devices.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(QueueKioskSettings::class);

        $cookieValue = $request->cookie('queue_kiosk_unlocked');

        if (! $settings->is_enabled || $cookieValue !== hash('sha256', $settings->pin)) {
            return redirect()->route('queue.kiosk.pin');
        }

        return $next($request);
    }
}
