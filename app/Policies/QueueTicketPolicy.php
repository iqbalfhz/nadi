<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QueueTicket;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QueueTicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QueueTicket');
    }

    public function view(AuthUser $authUser, QueueTicket $queueTicket): bool
    {
        return $authUser->can('View:QueueTicket');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QueueTicket');
    }

    public function update(AuthUser $authUser, QueueTicket $queueTicket): bool
    {
        return $authUser->can('Update:QueueTicket');
    }

    public function delete(AuthUser $authUser, QueueTicket $queueTicket): bool
    {
        return $authUser->can('Delete:QueueTicket');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QueueTicket');
    }

    public function restore(AuthUser $authUser, QueueTicket $queueTicket): bool
    {
        return $authUser->can('Restore:QueueTicket');
    }

    public function forceDelete(AuthUser $authUser, QueueTicket $queueTicket): bool
    {
        return $authUser->can('ForceDelete:QueueTicket');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QueueTicket');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QueueTicket');
    }

    public function replicate(AuthUser $authUser, QueueTicket $queueTicket): bool
    {
        return $authUser->can('Replicate:QueueTicket');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QueueTicket');
    }
}
