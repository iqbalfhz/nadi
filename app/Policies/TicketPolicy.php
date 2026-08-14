<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        // Every authenticated user may view the ticket sales report — the
        // /app "Laporan Penjualan Tiket" resource is deliberately NOT scoped
        // to the current user (any cashier needs to see the whole event's
        // combined sales to close out, not just their own), so this only
        // needs to gate reaching the list at all.
        return true;
    }

    public function view(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('View:Ticket') || $authUser->id === $ticket->sold_by;
    }

    public function create(AuthUser $authUser): bool
    {
        // Any authenticated user can sell a ticket from /app — there's no
        // dedicated cashier role. Note this is mostly symbolic: SellTicket
        // calls Ticket::sellFor() directly rather than going through a
        // CreateRecord page, so this policy method is never actually
        // invoked by that flow (same as QueueOperator/MessengerTasks never
        // triggering their models' create() policy either). The real gate
        // on "who can sell tickets" is simply reaching /app.
        return true;
    }

    public function update(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('Update:Ticket');
    }

    public function delete(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('Delete:Ticket');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Ticket');
    }

    public function restore(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('Restore:Ticket');
    }

    public function forceDelete(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('ForceDelete:Ticket');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ticket');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ticket');
    }

    public function replicate(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('Replicate:Ticket');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ticket');
    }
}
