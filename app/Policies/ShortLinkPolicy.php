<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShortLink;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ShortLinkPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShortLink');
    }

    public function view(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('View:ShortLink') || $authUser->id === $shortLink->created_by;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShortLink');
    }

    public function update(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('Update:ShortLink');
    }

    public function delete(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('Delete:ShortLink') || $authUser->id === $shortLink->created_by;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShortLink');
    }

    public function restore(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('Restore:ShortLink');
    }

    public function forceDelete(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('ForceDelete:ShortLink');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShortLink');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShortLink');
    }

    public function replicate(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('Replicate:ShortLink');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShortLink');
    }
}
