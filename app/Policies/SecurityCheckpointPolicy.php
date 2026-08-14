<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SecurityCheckpoint;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SecurityCheckpointPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SecurityCheckpoint');
    }

    public function view(AuthUser $authUser, SecurityCheckpoint $securityCheckpoint): bool
    {
        return $authUser->can('View:SecurityCheckpoint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SecurityCheckpoint');
    }

    public function update(AuthUser $authUser, SecurityCheckpoint $securityCheckpoint): bool
    {
        return $authUser->can('Update:SecurityCheckpoint');
    }

    public function delete(AuthUser $authUser, SecurityCheckpoint $securityCheckpoint): bool
    {
        return $authUser->can('Delete:SecurityCheckpoint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SecurityCheckpoint');
    }

    public function restore(AuthUser $authUser, SecurityCheckpoint $securityCheckpoint): bool
    {
        return $authUser->can('Restore:SecurityCheckpoint');
    }

    public function forceDelete(AuthUser $authUser, SecurityCheckpoint $securityCheckpoint): bool
    {
        return $authUser->can('ForceDelete:SecurityCheckpoint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SecurityCheckpoint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SecurityCheckpoint');
    }

    public function replicate(AuthUser $authUser, SecurityCheckpoint $securityCheckpoint): bool
    {
        return $authUser->can('Replicate:SecurityCheckpoint');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SecurityCheckpoint');
    }
}
