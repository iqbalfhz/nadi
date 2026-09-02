<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * "Masuk sebagai" — seeing NADI exactly as an employee sees it.
 *
 * Role configuration says what someone *should* see; this shows what they
 * actually see, and the gap between the two is where the bugs live. NADI
 * stacks three layers of roles (see NadiRoleSeeder), so that gap is rarely
 * obvious from the Users screen.
 *
 * Every rule lives here rather than in the button, the route and the tests
 * separately — a check that exists in three places eventually disagrees with
 * itself, and the disagreement is always the permissive one.
 */
final class Impersonation
{
    private const SESSION_KEY = 'nadi.impersonator_id';

    /**
     * Only super_admin, and never while already impersonating.
     *
     * Impersonating hands over the target's entire access. If a role like
     * `admin-pengguna` could do it, that user could impersonate a super_admin
     * and inherit everything — a support tool turned into a privilege
     * escalation path. One role, one simple rule.
     */
    public static function canImpersonate(User $actor): bool
    {
        return $actor->hasRole('super_admin') && ! self::isActive();
    }

    public static function isImpersonatable(User $actor, User $target): bool
    {
        if (! self::canImpersonate($actor)) {
            return false;
        }

        if ($actor->is($target)) {
            return false;
        }

        // Nothing is learned by stepping into an account that already sees
        // everything, and it blurs which of the two most powerful accounts did
        // what.
        if ($target->hasRole('super_admin')) {
            return false;
        }

        // A deactivated account is locked out on purpose. Impersonating it
        // would walk straight past that lock.
        if (! $target->is_active) {
            return false;
        }

        return self::landingPanelFor($target) !== null;
    }

    /**
     * Which panel the target would land on — and, by returning null, whether
     * they can reach any panel at all. Impersonating an account with no way in
     * only drops the admin on a 403 with no explanation.
     */
    public static function landingPanelFor(User $target): ?string
    {
        foreach (['admin', 'app'] as $panelId) {
            if ($target->canAccessPanel(Filament::getPanel($panelId))) {
                return $panelId;
            }
        }

        return null;
    }

    public static function start(User $actor, User $target): void
    {
        if (! self::isImpersonatable($actor, $target)) {
            return;
        }

        $actorId = $actor->getKey();

        // Logged before the swap, while the actor is still the causer — after
        // Auth::login() the entry would name the employee as having started
        // impersonating themselves.
        activity('akses')
            ->causedBy($actor)
            ->performedOn($target)
            ->log("Mulai masuk sebagai {$target->name}");

        // Regenerating on the way in *and* out: swapping identity while keeping
        // the same session id is the textbook session-fixation shape.
        Session::regenerate();
        Session::put(self::SESSION_KEY, $actorId);

        Auth::login($target);
    }

    /**
     * Returns the restored account, or null when nothing was being
     * impersonated — so callers can tell "finished" from "there was nothing
     * to finish" without asking twice.
     */
    public static function stop(): ?User
    {
        $impersonator = self::impersonator();

        if ($impersonator === null) {
            Session::forget(self::SESSION_KEY);

            return null;
        }

        $target = Auth::user();

        Session::forget(self::SESSION_KEY);
        Session::regenerate();

        Auth::login($impersonator);

        activity('akses')
            ->causedBy($impersonator)
            ->performedOn($target instanceof User ? $target : $impersonator)
            ->log('Selesai masuk sebagai '.($target instanceof User ? $target->name : 'pengguna lain'));

        return $impersonator;
    }

    public static function isActive(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * The real person behind the current session.
     *
     * A stale id — the account was deleted mid-session — is treated as "not
     * impersonating" rather than throwing, so a deleted admin cannot strand
     * someone inside another user's account.
     */
    public static function impersonator(): ?User
    {
        $id = Session::get(self::SESSION_KEY);

        if ($id === null) {
            return null;
        }

        // Cast before the lookup: find() hands back a Collection when given an
        // array, and the session value is only ever a single key.
        return User::query()->find((int) $id);
    }
}
