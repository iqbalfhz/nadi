<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ObArea;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ObAreaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ObArea');
    }

    public function view(AuthUser $authUser, ObArea $obArea): bool
    {
        return $authUser->can('View:ObArea');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ObArea');
    }

    public function update(AuthUser $authUser, ObArea $obArea): bool
    {
        return $authUser->can('Update:ObArea');
    }

    public function delete(AuthUser $authUser, ObArea $obArea): bool
    {
        return $authUser->can('Delete:ObArea');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ObArea');
    }

    public function restore(AuthUser $authUser, ObArea $obArea): bool
    {
        return $authUser->can('Restore:ObArea');
    }

    public function forceDelete(AuthUser $authUser, ObArea $obArea): bool
    {
        return $authUser->can('ForceDelete:ObArea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ObArea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ObArea');
    }

    public function replicate(AuthUser $authUser, ObArea $obArea): bool
    {
        return $authUser->can('Replicate:ObArea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ObArea');
    }
}
