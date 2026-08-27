<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Read-only, deliberately. An audit trail that its subjects can edit or
 * delete records nothing worth trusting, so every write action is refused
 * outright rather than gated on a permission that could be granted by
 * mistake. Rows only ever leave through the scheduled retention clean-up.
 */
class ActivityLogPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:ActivityLog');
    }

    public function view(User $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('View:ActivityLog');
    }

    public function create(User $authUser): bool
    {
        return false;
    }

    public function update(User $authUser, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $authUser, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }

    public function forceDelete(User $authUser, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function restore(User $authUser, ActivityLog $activityLog): bool
    {
        return false;
    }
}
