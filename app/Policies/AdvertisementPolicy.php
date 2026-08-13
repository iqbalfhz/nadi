<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Advertisement;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AdvertisementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Advertisement');
    }

    public function view(AuthUser $authUser, Advertisement $advertisement): bool
    {
        return $authUser->can('View:Advertisement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Advertisement');
    }

    public function update(AuthUser $authUser, Advertisement $advertisement): bool
    {
        return $authUser->can('Update:Advertisement');
    }

    public function delete(AuthUser $authUser, Advertisement $advertisement): bool
    {
        return $authUser->can('Delete:Advertisement');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Advertisement');
    }

    public function restore(AuthUser $authUser, Advertisement $advertisement): bool
    {
        return $authUser->can('Restore:Advertisement');
    }

    public function forceDelete(AuthUser $authUser, Advertisement $advertisement): bool
    {
        return $authUser->can('ForceDelete:Advertisement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Advertisement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Advertisement');
    }

    public function replicate(AuthUser $authUser, Advertisement $advertisement): bool
    {
        return $authUser->can('Replicate:Advertisement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Advertisement');
    }
}
