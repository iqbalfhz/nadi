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
        return $authUser->can('ViewAny:ObChecklist');
    }

    public function view(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        return $authUser->can('View:ObChecklist') || $authUser->id === $obChecklist->user_id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ObChecklist');
    }

    public function update(AuthUser $authUser, ObChecklist $obChecklist): bool
    {
        // No longer immutable: /admin has an edit page. What it edits is
        // narrow on purpose — the note only, never the area, the photos, the
        // worker or the times. That limit lives in ObChecklistForm rather
        // than here, because it is about which fields a correction may touch,
        // not about who may correct.
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
