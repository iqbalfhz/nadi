<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HkInspectionController;
use App\Http\Controllers\Api\V1\MeController;
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

    Route::middleware(['auth:sanctum', 'ability:'.AuthController::MOBILE_ABILITY])->group(function (): void {
        // Outside the mobile-access gate on purpose: someone whose access was
        // just revoked should still be able to clean up their own token.
        Route::post('auth/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::middleware([EnsureMobileAccess::class, SetInterfaceLanguage::class])->group(function (): void {
            Route::get('me', MeController::class)->name('me');

            Route::get('ob/areas', [ObChecklistController::class, 'areas'])->name('ob.areas');
            Route::get('ob/checklists', [ObChecklistController::class, 'index'])->name('ob.index');
            Route::get('ob/checklists/{obChecklist}', [ObChecklistController::class, 'show'])->name('ob.show');
            Route::get('ob/checklists/{obChecklist}/photos', [ObChecklistController::class, 'photos'])->name('ob.photos');

            // No "list all checkpoints" route, on purpose — the code on the QR
            // sticker is the evidence a guard reached the post, so the app
            // resolves the one code it just scanned and never holds the set.
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

            // Everything that writes. The idempotency key is required here,
            // not optional: these are the calls a phone retries after losing
            // signal, and a retry must never produce a second report.
            Route::middleware(EnsureIdempotency::class)->group(function (): void {
                Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
                Route::post('ob/checklists', [ObChecklistController::class, 'store'])->name('ob.store');
                Route::post('security/patrols', [SecurityPatrolController::class, 'store'])->name('security.store');
                Route::post('hk/inspections', [HkInspectionController::class, 'store'])->name('hk.store');
            });
        });
    });
});
