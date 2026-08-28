<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Works out where the "back to" button on an error page should actually
 * send someone, from where they were and what they can actually reach.
 *
 * The destination is checked against the panel gate, not just guessed from
 * the URL. Getting that wrong strands people: a 403 at /admin used to offer
 * "Kembali ke Dashboard Admin", which sent an employee straight back to the
 * page that had just refused them, over and over.
 *
 * NADI has four places a request can die in — the admin panel, the employee
 * panel, the unattended Antrian screens, and the public pages — and each has
 * a different sensible destination. Nobody standing at the lobby kiosk can
 * log in, and an employee bounced to /admin only lands on another wall.
 */
final class ErrorPageDestination
{
    /**
     * @return array{label: string, url: string, method: string}
     */
    public static function resolve(): array
    {
        // Error pages have to render even when the thing that broke is the
        // database or the session store, so nothing in here may throw:
        // Auth::check() alone reaches for both.
        try {
            return self::determine();
        } catch (Throwable) {
            return self::get('Kembali ke Halaman Utama', url('/'));
        }
    }

    /**
     * @return array{label: string, url: string, method: string}
     */
    private static function determine(): array
    {
        $path = trim(request()->path(), '/');

        // The queue screens run unattended — a tablet in the lobby and a TV
        // on the wall. Whoever is looking at them can't log in, so they get
        // sent back to the screen they came from.
        if (str_starts_with($path, 'antrian/layar')) {
            return self::get('Kembali ke Layar Antrian', route('queue.display'));
        }

        if (str_starts_with($path, 'antrian')) {
            return self::get('Kembali ke Kiosk Antrian', route('queue.kiosk.take'));
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return self::get('Masuk ke NADI', route('login'));
        }

        $canAdmin = $user->canAccessPanel(Filament::getPanel('admin'));
        $canApp = $user->canAccessPanel(Filament::getPanel('app'));

        // Where they were, but only if they can actually get back in.
        if (str_starts_with($path, 'admin') && $canAdmin) {
            return self::get('Kembali ke Dashboard Admin', url('/admin'));
        }

        if (str_starts_with($path, 'app') && $canApp) {
            return self::get('Kembali ke Dashboard', url('/app'));
        }

        // Otherwise the panel they do belong in — this is what catches an
        // employee who typed /admin: they belong in /app, not back at the
        // wall they just hit.
        if ($canApp) {
            return self::get('Kembali ke Dashboard', url('/app'));
        }

        if ($canAdmin) {
            return self::get('Kembali ke Dashboard Admin', url('/admin'));
        }

        // Signed in but able to reach neither panel — an account whose role
        // holds nothing at all. There is no page to offer, so offer the one
        // action that helps: sign out and try another account.
        return [
            'label' => 'Keluar',
            'url' => route('logout'),
            'method' => 'post',
        ];
    }

    /**
     * @return array{label: string, url: string, method: string}
     */
    private static function get(string $label, string $url): array
    {
        return ['label' => $label, 'url' => $url, 'method' => 'get'];
    }
}
