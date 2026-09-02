<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class PreventInactiveUserLogin
{
    /**
     * Every authentication method (password, 2FA, the remember-me cookie)
     * ends by calling Auth::login(), which fires this event — hooking here
     * catches all of them from one place, rather than gating each login path
     * separately (Fortify::authenticateUsing() only covers the password grant).
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User || $event->user->is_active) {
            return;
        }

        Auth::logout();

        throw ValidationException::withMessages([
            Fortify::username() => 'Akun ini sudah dinonaktifkan. Hubungi admin.',
        ]);
    }
}
