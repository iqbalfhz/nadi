<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QueueCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QueueCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QueueCategory');
    }

    public function view(AuthUser $authUser, QueueCategory $queueCategory): bool
    {
        return $authUser->can('View:QueueCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QueueCategory');
    }

    public function update(AuthUser $authUser, QueueCategory $queueCategory): bool
    {
        return $authUser->can('Update:QueueCategory');
    }

    public function delete(AuthUser $authUser, QueueCategory $queueCategory): bool
    {
        return $authUser->can('Delete:QueueCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QueueCategory');
    }

    public function restore(AuthUser $authUser, QueueCategory $queueCategory): bool
    {
        return $authUser->can('Restore:QueueCategory');
    }

    public function forceDelete(AuthUser $authUser, QueueCategory $queueCategory): bool
    {
        return $authUser->can('ForceDelete:QueueCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QueueCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QueueCategory');
    }

    public function replicate(AuthUser $authUser, QueueCategory $queueCategory): bool
    {
        return $authUser->can('Replicate:QueueCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QueueCategory');
    }
}
