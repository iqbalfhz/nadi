<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Barcode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BarcodePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Barcode');
    }

    public function view(AuthUser $authUser, Barcode $barcode): bool
    {
        return $authUser->can('View:Barcode') || $authUser->id === $barcode->created_by;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Barcode');
    }

    public function update(AuthUser $authUser, Barcode $barcode): bool
    {
        return $authUser->can('Update:Barcode');
    }

    public function delete(AuthUser $authUser, Barcode $barcode): bool
    {
        return $authUser->can('Delete:Barcode') || $authUser->id === $barcode->created_by;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Barcode');
    }

    public function restore(AuthUser $authUser, Barcode $barcode): bool
    {
        return $authUser->can('Restore:Barcode');
    }

    public function forceDelete(AuthUser $authUser, Barcode $barcode): bool
    {
        return $authUser->can('ForceDelete:Barcode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Barcode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Barcode');
    }

    public function replicate(AuthUser $authUser, Barcode $barcode): bool
    {
        return $authUser->can('Replicate:Barcode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Barcode');
    }
}
