<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CrashReportController;
use App\Http\Controllers\Api\V1\HkInspectionController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MessengerTaskController;
use App\Http\Controllers\Api\V1\ObChecklistController;
use App\Http\Controllers\Api\V1\SecurityPatrolController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Middleware\EnsureIdempotency;
use App\Http\Middleware\EnsureMobileAccess;
use App\Http\Middleware\SetInterfaceLanguage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API
|--------------------------------------------------------------------------
|
| Consumed by the Flutter app; see docs/API-MOBILE.md for the contract.
|
| Versioned from the first endpoint, which the web never needed: a phone
| runs whatever version its owner last installed, and people do not update
| in step. Without a version in the path, one breaking change stops every
| handset that hasn't been updated. Moving the endpoints later would cost
| far more than the segment does now.
|
| Controllers, not closures. Route::get('...', fn () => ...) would still
| cache (Laravel serialises closures), but form-request injection and
| policy calls both want a real method to live in.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->name('auth.login');

    // The second half of a two-factor sign-in. Reachable only with the
    // short-lived challenge token, which carries no other ability — a
    // half-finished login cannot touch anything real.
    Route::post('auth/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])
        ->middleware(['auth:sanctum', 'ability:'.AuthController::CHALLENGE_ABILITY])
        ->name('auth.two-factor-challenge');

    // Laravel 11 dropped throttle:api from the default group, so without this
    // the API has no ceiling at all. Deliberately generous rather than
    // conventional: an outbox coming back from a basement flushes a whole
    // shift at once — twelve reports plus their photos is ~50 requests in a
    // few seconds, and a 60/min limit would strand exactly the case the
    // offline design exists to serve. This is a runaway-client guard, not a
    // usage quota. Login has its own tighter limit in AuthController.
    Route::middleware(['auth:sanctum', 'ability:'.AuthController::MOBILE_ABILITY, 'throttle:240,1'])->group(function (): void {
        // Outside the mobile-access gate on purpose: someone whose access was
        // just revoked should still be able to clean up their own token.
        Route::post('auth/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        // Outside the mobile-access gate for the same reason as logout, and a
        // sharper one: an account that just lost its access is a plausible
        // cause of the crash being reported.
        //
        // Its own ceiling on top of the shared 240. A screen that fails on
        // every rebuild can fire reports as fast as the loop runs, and that
        // burst must not eat the budget the outbox needs to flush a shift's
        // work. Deduplication happens in the model, so a client held at this
        // limit loses repeats, never the first sighting of something new.
        Route::post('crash', CrashReportController::class)
            ->middleware('throttle:12,1')
            ->name('crash');

        Route::middleware([EnsureMobileAccess::class, SetInterfaceLanguage::class])->group(function (): void {
            Route::get('me', MeController::class)->name('me');

            Route::get('ob/areas', [ObChecklistController::class, 'areas'])->name('ob.areas');
            Route::get('ob/checklists', [ObChecklistController::class, 'index'])->name('ob.index');
            Route::get('ob/checklists/{obChecklist}', [ObChecklistController::class, 'show'])->name('ob.show');
            Route::get('ob/checklists/{obChecklist}/photos', [ObChecklistController::class, 'photos'])->name('ob.photos');

            // No "list all checkpoints" route, on purpose — the code on the QR
            // sticker is the evidence a guard reached the post, so the app
            // resolves the one code it just scanned and never holds the set.
            // Takes the raw QR content: stickers hold a URL, and a URL cannot
            // ride in a path segment. This is the one the app should call.
            Route::get('security/scan', [SecurityPatrolController::class, 'scan'])->name('security.scan');
            Route::get('security/checkpoints/{code}', [SecurityPatrolController::class, 'resolve'])->name('security.resolve');
            Route::get('security/patrols', [SecurityPatrolController::class, 'index'])->name('security.index');
            Route::get('security/patrols/{securityPatrol}/photos', [SecurityPatrolController::class, 'photos'])->name('security.photos');

            // Categories carry their points and their requires_floor flag, so
            // one cached call is enough to render the conditional form offline.
            Route::get('hk/categories', [HkInspectionController::class, 'categories'])->name('hk.categories');
            Route::get('hk/options', [HkInspectionController::class, 'options'])->name('hk.options');
            Route::get('hk/inspections', [HkInspectionController::class, 'index'])->name('hk.index');
            Route::get('hk/inspections/{hkInspection}', [HkInspectionController::class, 'show'])->name('hk.show');
            Route::get('hk/inspections/{hkInspection}/photos', [HkInspectionController::class, 'photos'])->name('hk.photos');

            // Open tasks are module-wide on purpose — self-pickup means a
            // courier has to see what nobody has taken yet.
            Route::get('messenger/tasks/open', [MessengerTaskController::class, 'open'])->name('messenger.open');
            Route::get('messenger/tasks/mine', [MessengerTaskController::class, 'mine'])->name('messenger.mine');
            Route::get('messenger/tasks/{messengerDelivery}/proof', [MessengerTaskController::class, 'proof'])->name('messenger.proof');

            // Everything that writes. The idempotency key is required here,
            // not optional: these are the calls a phone retries after losing
            // signal, and a retry must never produce a second report.
            Route::middleware(EnsureIdempotency::class)->group(function (): void {
                Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
                Route::post('ob/checklists', [ObChecklistController::class, 'store'])->name('ob.store');
                Route::post('security/patrols', [SecurityPatrolController::class, 'store'])->name('security.store');
                Route::post('hk/inspections', [HkInspectionController::class, 'store'])->name('hk.store');

                // claim() must never be queued offline — see
                // MessengerTaskController::claim().
                Route::post('messenger/tasks/{delivery}/claim', [MessengerTaskController::class, 'claim'])->name('messenger.claim');
                Route::post('messenger/tasks/{messengerDelivery}/transit', [MessengerTaskController::class, 'transit'])->name('messenger.transit');
                Route::post('messenger/tasks/{messengerDelivery}/deliver', [MessengerTaskController::class, 'deliver'])->name('messenger.deliver');
            });
        });
    });
});
