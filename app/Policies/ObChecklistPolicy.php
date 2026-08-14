<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ObChecklist;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ObChecklistPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        // Every authenticated user may view a list of checklists — the "Checklist
        // Saya" resource on the /app panel scopes its query to the current user,
        // so this only needs to gate reaching that list, not which rows appear.
        return true;
    }

    public function view(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        return $authUser->can('View:ObChecklist') || $authUser->id === $obChecklist->user_id;
    }

    public function create(AuthUser $authUser): bool
    {
        // Any authenticated user can submit a checklist from /app — OB staff
        // have no dedicated role/permissions yet, so this isn't gated behind
        // Shield's Create:ObChecklist permission (which only admins hold).
        return true;
    }

    public function update(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        // Immutable once submitted — no edit page exists for this resource in
        // either panel, so this only matters if it's ever checked directly.
        return $authUser->can('Update:ObChecklist');
    }

    public function delete(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        return $authUser->can('Delete:ObChecklist');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ObChecklist');
    }

    public function restore(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        return $authUser->can('Restore:ObChecklist');
    }

    public function forceDelete(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        return $authUser->can('ForceDelete:ObChecklist');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ObChecklist');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ObChecklist');
    }

    public function replicate(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        return $authUser->can('Replicate:ObChecklist');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ObChecklist');
    }
}
