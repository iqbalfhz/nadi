<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HkArea;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HkAreaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HkArea');
    }

    public function view(AuthUser $authUser, HkArea $hkArea): bool
    {
        return $authUser->can('View:HkArea');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HkArea');
    }

    public function update(AuthUser $authUser, HkArea $hkArea): bool
    {
        return $authUser->can('Update:HkArea');
    }

    public function delete(AuthUser $authUser, HkArea $hkArea): bool
    {
        return $authUser->can('Delete:HkArea');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HkArea');
    }

    public function restore(AuthUser $authUser, HkArea $hkArea): bool
    {
        return $authUser->can('Restore:HkArea');
    }

    public function forceDelete(AuthUser $authUser, HkArea $hkArea): bool
    {
        return $authUser->can('ForceDelete:HkArea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HkArea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HkArea');
    }

    public function replicate(AuthUser $authUser, HkArea $hkArea): bool
    {
        return $authUser->can('Replicate:HkArea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HkArea');
    }
}
