<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HkCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HkCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HkCategory');
    }

    public function view(AuthUser $authUser, HkCategory $hkCategory): bool
    {
        return $authUser->can('View:HkCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HkCategory');
    }

    public function update(AuthUser $authUser, HkCategory $hkCategory): bool
    {
        return $authUser->can('Update:HkCategory');
    }

    public function delete(AuthUser $authUser, HkCategory $hkCategory): bool
    {
        return $authUser->can('Delete:HkCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HkCategory');
    }

    public function restore(AuthUser $authUser, HkCategory $hkCategory): bool
    {
        return $authUser->can('Restore:HkCategory');
    }

    public function forceDelete(AuthUser $authUser, HkCategory $hkCategory): bool
    {
        return $authUser->can('ForceDelete:HkCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HkCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HkCategory');
    }

    public function replicate(AuthUser $authUser, HkCategory $hkCategory): bool
    {
        return $authUser->can('Replicate:HkCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HkCategory');
    }
}
