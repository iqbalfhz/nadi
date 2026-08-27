<?php

use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public redirect — no login, since whoever clicks a shared short link has
// no NADI account. The DB unique constraint on `code` means firstOrFail()
// here is a genuine 404, not a lookup ambiguity.
Route::get('s/{code}', function (Request $request, string $code) {
    $shortLink = ShortLink::query()->where('code', $code)->firstOrFail();

    // increment() is a raw, atomic DB-level update (no lost-update race if
    // two clicks land at once) and already bypasses mass-assignment
    // guarding on its own. last_clicked_at isn't fillable either — it's
    // internal bookkeeping, not user input — so forceFill() bypasses that
    // guard for just this one field.
    $shortLink->increment('clicks');
    $shortLink->forceFill(['last_clicked_at' => now()])->save();

    // Belt and braces: the create form only accepts http/https, but a
    // redirect target is the last place to take that on trust.
    abort_unless($shortLink->hasFollowableScheme(), 404);

    // A short link dresses an arbitrary destination in this office's own
    // domain, so an unrecognised one is shown to whoever clicked before they
    // land on it — otherwise any employee who can create links could phish a
    // colleague behind a URL that looks internal.
    if (! $shortLink->hasTrustedDestination() && ! $request->boolean('lanjut')) {
        return response()->view('short-links.confirm', [
            'shortLink' => $shortLink,
        ]);
    }

    return redirect()->away($shortLink->target_url);
})->name('short-links.redirect');
