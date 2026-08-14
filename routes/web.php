<?php

use Illuminate\Support\Facades\Route;

// NADI is an internal tool, not a public product — there is no marketing
// landing page. Guests land on login, authenticated users bounce onward
// via the /dashboard route below.
Route::redirect('/', '/dashboard')->name('home');

// NADI has no standalone dashboard view of its own — this route only exists so
// Fortify's post-login redirect (config('fortify.home')) and existing named-route
// references have somewhere to land, then bounces into the right Filament panel.
Route::get('dashboard', function () {
    $user = auth()->user();

    return redirect($user->hasRole('super_admin') ? '/admin' : '/app');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/queue.php';
require __DIR__.'/security.php';
