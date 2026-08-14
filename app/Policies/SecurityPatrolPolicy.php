<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SecurityPatrol;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SecurityPatrolPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SecurityPatrol');
    }

    public function view(AuthUser $authUser, SecurityPatrol $securityPatrol): bool
    {
        return $authUser->can('View:SecurityPatrol');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SecurityPatrol');
    }

    public function update(AuthUser $authUser, SecurityPatrol $securityPatrol): bool
    {
        return $authUser->can('Update:SecurityPatrol');
    }

    public function delete(AuthUser $authUser, SecurityPatrol $securityPatrol): bool
    {
        return $authUser->can('Delete:SecurityPatrol');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SecurityPatrol');
    }

    public function restore(AuthUser $authUser, SecurityPatrol $securityPatrol): bool
    {
        return $authUser->can('Restore:SecurityPatrol');
    }

    public function forceDelete(AuthUser $authUser, SecurityPatrol $securityPatrol): bool
    {
        return $authUser->can('ForceDelete:SecurityPatrol');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SecurityPatrol');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SecurityPatrol');
    }

    public function replicate(AuthUser $authUser, SecurityPatrol $securityPatrol): bool
    {
        return $authUser->can('Replicate:SecurityPatrol');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SecurityPatrol');
    }
}
