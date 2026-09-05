<?php

namespace App\Policies;

use App\Models\AppCrashReport;
use App\Models\User;

/**
 * Read-only, and not just by omission — every write action is refused
 * outright rather than gated on a permission somebody could be granted by
 * mistake. Nothing writes these rows but the handsets, and nobody should be
 * able to tidy away the evidence that the app is failing in the field.
 *
 * Shield generates the full twelve permissions for any resource; only the two
 * read ones are ever consulted. Rows leave through the retention clean-up in
 * PruneApiStaging and no other way.
 */
class AppCrashReportPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:AppCrashReport');
    }

    public function view(User $authUser, AppCrashReport $appCrashReport): bool
    {
        return $authUser->can('View:AppCrashReport');
    }

    public function create(User $authUser): bool
    {
        return false;
    }

    public function update(User $authUser, AppCrashReport $appCrashReport): bool
    {
        return false;
    }

    public function delete(User $authUser, AppCrashReport $appCrashReport): bool
    {
        return false;
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }

    public function forceDelete(User $authUser, AppCrashReport $appCrashReport): bool
    {
        return false;
    }

    public function restore(User $authUser, AppCrashReport $appCrashReport): bool
    {
        return false;
    }
}
