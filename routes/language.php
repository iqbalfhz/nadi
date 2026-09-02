<?php

use App\Models\User;
use App\Support\InterfaceLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
 * Switching the interface language.
 *
 * A POST because it writes to the account, and because a GET would be
 * followable from a link or a browser prefetch. Called from the two buttons in
 * the user menu.
 */
Route::post('language', function (Request $request) {
    $user = Auth::user();

    $locale = (string) $request->input('locale');

    if ($user instanceof User && InterfaceLanguage::isSupported($locale)) {
        InterfaceLanguage::remember($user, $locale);
    }

    // Back where they were: the choice is a preference, not a navigation.
    return back();
})->middleware('auth')->name('language.switch');
