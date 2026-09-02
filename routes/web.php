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

    // hasAnyAdminPermission() (not hardcoding hasRole('super_admin')) — any
    // role holding an admin-relevant permission now lands on /admin, not
    // just super_admin. canAccessPanel('admin') can't be used for this: it
    // only checks is_active, since every active employee may attempt
    // /admin by design, whether or not they have anything to do there.
    return redirect($user->hasAnyAdminPermission() ? '/admin' : '/app');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/impersonation.php';
require __DIR__.'/language.php';
require __DIR__.'/legal.php';
require __DIR__.'/queue.php';
require __DIR__.'/security.php';
require __DIR__.'/short-links.php';
require __DIR__.'/barcodes.php';
