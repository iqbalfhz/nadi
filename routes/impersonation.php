<?php

use App\Support\Impersonation;
use Illuminate\Support\Facades\Route;

/*
 * The way back out of "Masuk sebagai".
 *
 * A POST, not a GET: this changes who you are signed in as, and a GET could be
 * triggered by a link or a browser prefetch. Called from the banner that sits
 * at the top of every page while impersonation is active.
 *
 * Impersonation::stop() is a no-op when nothing is being impersonated, so this
 * needs no guard of its own — either way the answer is "go to wherever this
 * account belongs", which /dashboard already decides.
 */
Route::post('impersonation/stop', function () {
    Impersonation::stop();

    return redirect('/dashboard');
})->middleware('auth')->name('impersonation.stop');
