<?php

namespace App\Providers\Filament\Concerns;

use App\Models\User;
use App\Support\Impersonation;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;

/**
 * The banner that sits above everything while "Masuk sebagai" is active.
 *
 * Deliberately not dismissible. An admin who forgets they are impersonating
 * reads every missing menu as a bug in NADI, and reports it as one — the whole
 * value of the feature depends on never being in any doubt about whose screen
 * you are looking at.
 *
 * Rendered by both panels, because the target may land in either one.
 */
trait HasImpersonationBanner
{
    protected function withImpersonationBanner(Panel $panel): Panel
    {
        return $panel->renderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => self::impersonationBanner(),
        );
    }

    private static function impersonationBanner(): string
    {
        if (! Impersonation::isActive()) {
            return '';
        }

        $targetUser = Auth::user();
        $impersonatorUser = Impersonation::impersonator();

        // Naming both accounts is the entire point of the banner. If either is
        // somehow missing, a half-written warning would confuse more than the
        // absence of one — the stop route stays reachable either way.
        if (! $targetUser instanceof User || ! $impersonatorUser instanceof User) {
            return '';
        }

        $target = e($targetUser->name);
        $impersonator = e($impersonatorUser->name);
        $action = e(route('impersonation.stop'));
        $token = e(csrf_token());

        // Inline styles rather than Tailwind classes: this ships from a render
        // hook, outside any panel's compiled stylesheet, and must look right in
        // both panels whether or not either has its own build.
        return <<<HTML
            <div style="position:sticky;top:0;z-index:50;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.75rem;padding:.625rem 1rem;background:#b45309;color:#fff;font-size:.875rem;line-height:1.4;">
                <span>
                    Anda sedang masuk sebagai <strong>{$target}</strong>.
                    Tampilan ini milik dia, bukan akun Anda ({$impersonator}).
                </span>
                <form method="POST" action="{$action}" style="margin:0;">
                    <input type="hidden" name="_token" value="{$token}">
                    <button type="submit" style="cursor:pointer;border-radius:.5rem;border:1px solid rgba(255,255,255,.5);background:rgba(255,255,255,.15);padding:.25rem .75rem;color:#fff;font-weight:600;font-size:.8125rem;">
                        Kembali ke akun saya
                    </button>
                </form>
            </div>
            HTML;
    }
}
