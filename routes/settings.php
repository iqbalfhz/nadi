<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'verified'])->group(function () {
    // Name/email/password now live on Filament's native profile page instead
    // (see AdminPanelProvider/AppPanelProvider ->profile()); only 2FA, passkeys,
    // and appearance still need this shared, non-Filament settings area.
    Route::redirect('settings', 'settings/security');

    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
