<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bazaar;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BazaarPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Bazaar');
    }

    public function view(AuthUser $authUser, Bazaar $bazaar): bool
    {
        return $authUser->can('View:Bazaar');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Bazaar');
    }

    public function update(AuthUser $authUser, Bazaar $bazaar): bool
    {
        return $authUser->can('Update:Bazaar');
    }

    public function delete(AuthUser $authUser, Bazaar $bazaar): bool
    {
        return $authUser->can('Delete:Bazaar');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Bazaar');
    }

    public function restore(AuthUser $authUser, Bazaar $bazaar): bool
    {
        return $authUser->can('Restore:Bazaar');
    }

    public function forceDelete(AuthUser $authUser, Bazaar $bazaar): bool
    {
        return $authUser->can('ForceDelete:Bazaar');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Bazaar');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Bazaar');
    }

    public function replicate(AuthUser $authUser, Bazaar $bazaar): bool
    {
        return $authUser->can('Replicate:Bazaar');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Bazaar');
    }
}
