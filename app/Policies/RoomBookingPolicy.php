<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RoomBooking;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RoomBookingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RoomBooking');
    }

    public function view(AuthUser $authUser, RoomBooking $roomBooking): bool
    {
        return $authUser->can('View:RoomBooking');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RoomBooking');
    }

    public function update(AuthUser $authUser, RoomBooking $roomBooking): bool
    {
        return $authUser->can('Update:RoomBooking');
    }

    public function delete(AuthUser $authUser, RoomBooking $roomBooking): bool
    {
        return $authUser->can('Delete:RoomBooking');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RoomBooking');
    }

    public function restore(AuthUser $authUser, RoomBooking $roomBooking): bool
    {
        return $authUser->can('Restore:RoomBooking');
    }

    public function forceDelete(AuthUser $authUser, RoomBooking $roomBooking): bool
    {
        return $authUser->can('ForceDelete:RoomBooking');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RoomBooking');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RoomBooking');
    }

    public function replicate(AuthUser $authUser, RoomBooking $roomBooking): bool
    {
        return $authUser->can('Replicate:RoomBooking');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RoomBooking');
    }
}
