<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HkInspection;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HkInspectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HkInspection');
    }

    /**
     * A supervisor can always open a report they filed themselves, even
     * without the company-wide View permission — the /app resource is scoped
     * to their own rows and would otherwise refuse to show them. Same shape as
     * ObChecklistPolicy::view().
     */
    public function view(AuthUser $authUser, HkInspection $hkInspection): bool
    {
        return $authUser->can('View:HkInspection') || $authUser->id === $hkInspection->user_id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HkInspection');
    }

    public function update(AuthUser $authUser, HkInspection $hkInspection): bool
    {
        return $authUser->can('Update:HkInspection');
    }

    public function delete(AuthUser $authUser, HkInspection $hkInspection): bool
    {
        return $authUser->can('Delete:HkInspection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HkInspection');
    }

    public function restore(AuthUser $authUser, HkInspection $hkInspection): bool
    {
        return $authUser->can('Restore:HkInspection');
    }

    public function forceDelete(AuthUser $authUser, HkInspection $hkInspection): bool
    {
        return $authUser->can('ForceDelete:HkInspection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HkInspection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HkInspection');
    }

    public function replicate(AuthUser $authUser, HkInspection $hkInspection): bool
    {
        return $authUser->can('Replicate:HkInspection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HkInspection');
    }
}
