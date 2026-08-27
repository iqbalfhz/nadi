<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Works out where the "back to" button on an error page should actually
 * send someone, from where they were when it broke.
 *
 * NADI has four separate places a request can die in — the admin panel, the
 * employee panel, the unattended Antrian kiosk/display screens, and the
 * public pages — and each has a different sensible destination. A single
 * hard-coded link home would strand whoever hit the error: nobody standing
 * at the lobby kiosk can log in, and an employee bounced to /admin only
 * lands on another wall.
 */
final class ErrorPageDestination
{
    /**
     * @return array{label: string, url: string}
     */
    public static function resolve(): array
    {
        // Error pages have to render even when the thing that broke is the
        // database or the session store, so nothing in here may throw:
        // Auth::check() alone reaches for both.
        try {
            return self::determine();
        } catch (Throwable) {
            return ['label' => 'Kembali ke Halaman Utama', 'url' => url('/')];
        }
    }

    /**
     * @return array{label: string, url: string}
     */
    private static function determine(): array
    {
        $path = trim(request()->path(), '/');

        // The queue screens run unattended — a tablet in the lobby and a TV
        // on the wall. Whoever is looking at them can't log in, so they get
        // sent back to the screen they came from.
        if (str_starts_with($path, 'antrian/layar')) {
            return ['label' => 'Kembali ke Layar Antrian', 'url' => route('queue.display')];
        }

        if (str_starts_with($path, 'antrian')) {
            return ['label' => 'Kembali ke Kiosk Antrian', 'url' => route('queue.kiosk.take')];
        }

        if (! Auth::check()) {
            return ['label' => 'Masuk ke NADI', 'url' => route('login')];
        }

        if (str_starts_with($path, 'admin')) {
            return ['label' => 'Kembali ke Dashboard Admin', 'url' => url('/admin')];
        }

        if (str_starts_with($path, 'app')) {
            return ['label' => 'Kembali ke Dashboard', 'url' => url('/app')];
        }

        // Anywhere else: /dashboard already decides which panel this user
        // belongs in, so let it do that rather than guessing here.
        return ['label' => 'Kembali ke Dashboard', 'url' => route('dashboard')];
    }
}
