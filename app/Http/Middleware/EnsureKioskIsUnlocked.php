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
     * log in with. This checks for a cookie holding a token derived from the
     * *current* admin-set PIN (see QueueKioskSettings::unlockToken() and
     * ManageQueueKioskSettings) — rotating the PIN or disabling the kiosk
     * immediately invalidates every kiosk that was previously unlocked,
     * without needing to track devices.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(QueueKioskSettings::class);

        $cookieValue = $request->cookie('queue_kiosk_unlocked');

        // hash_equals, not !==: comparing secrets byte-by-byte leaks how much
        // of a guess was correct through how long the comparison took.
        if (! $settings->is_enabled || ! is_string($cookieValue) || ! hash_equals($settings->unlockToken(), $cookieValue)) {
            return redirect()->route('queue.kiosk.pin');
        }

        return $next($request);
    }
}
