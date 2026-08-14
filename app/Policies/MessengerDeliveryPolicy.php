<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MessengerDelivery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MessengerDeliveryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        // Every authenticated user may view a list of deliveries — the
        // "Pengiriman Saya" resource on the /app panel scopes its query to
        // the current user (sender_id), so this only needs to gate reaching
        // that list, not which rows appear in it.
        return true;
    }

    public function view(AuthUser $authUser, MessengerDelivery $messengerDelivery): bool
    {
        return $authUser->can('View:MessengerDelivery')
            || $authUser->id === $messengerDelivery->sender_id
            || $authUser->id === $messengerDelivery->messenger_id;
    }

    public function create(AuthUser $authUser): bool
    {
        // Any authenticated user can submit a delivery request from /app —
        // employees have no dedicated role/permissions yet, so this isn't
        // gated behind Shield's Create:MessengerDelivery permission (which
        // only admins hold).
        return true;
    }

    public function update(AuthUser $authUser, MessengerDelivery $messengerDelivery): bool
    {
        return $authUser->can('Update:MessengerDelivery');
    }

    public function delete(AuthUser $authUser, MessengerDelivery $messengerDelivery): bool
    {
        return $authUser->can('Delete:MessengerDelivery');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MessengerDelivery');
    }

    public function restore(AuthUser $authUser, MessengerDelivery $messengerDelivery): bool
    {
        return $authUser->can('Restore:MessengerDelivery');
    }

    public function forceDelete(AuthUser $authUser, MessengerDelivery $messengerDelivery): bool
    {
        return $authUser->can('ForceDelete:MessengerDelivery');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MessengerDelivery');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MessengerDelivery');
    }

    public function replicate(AuthUser $authUser, MessengerDelivery $messengerDelivery): bool
    {
        return $authUser->can('Replicate:MessengerDelivery');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MessengerDelivery');
    }
}
