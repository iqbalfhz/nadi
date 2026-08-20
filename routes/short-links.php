<?php

use App\Models\ShortLink;
use Illuminate\Support\Facades\Route;

// Public redirect — no login, since whoever clicks a shared short link has
// no NADI account. The DB unique constraint on `code` means firstOrFail()
// here is a genuine 404, not a lookup ambiguity.
Route::get('s/{code}', function (string $code) {
    $shortLink = ShortLink::query()->where('code', $code)->firstOrFail();

    // increment() is a raw, atomic DB-level update (no lost-update race if
    // two clicks land at once) and already bypasses mass-assignment
    // guarding on its own. last_clicked_at isn't fillable either — it's
    // internal bookkeeping, not user input — so forceFill() bypasses that
    // guard for just this one field.
    $shortLink->increment('clicks');
    $shortLink->forceFill(['last_clicked_at' => now()])->save();

    return redirect()->away($shortLink->target_url);
})->name('short-links.redirect');
