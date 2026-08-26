<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\VendorSale;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VendorSalePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        // The /app "Laporan Penjualan Bazar" resource is deliberately NOT
        // scoped to the current user (any cashier needs to see the whole
        // bazaar's combined sales to close out, not just their own) — but
        // reaching the list at all still requires this permission.
        return $authUser->can('ViewAny:VendorSale');
    }

    public function view(AuthUser $authUser, VendorSale $vendorSale): bool
    {
        return $authUser->can('View:VendorSale') || $authUser->id === $vendorSale->sold_by;
    }

    public function create(AuthUser $authUser): bool
    {
        // Note this is mostly symbolic: SellVendorProduct calls
        // VendorSale::sellFor() directly rather than going through a
        // CreateRecord page, so this policy method is never actually invoked
        // by that flow. The real gate on "who can sell" is the
        // SellVendorProduct page's own HasPageShield-backed permission
        // (View:SellVendorProduct).
        return $authUser->can('Create:VendorSale');
    }

    public function update(AuthUser $authUser, VendorSale $vendorSale): bool
    {
        return $authUser->can('Update:VendorSale');
    }

    public function delete(AuthUser $authUser, VendorSale $vendorSale): bool
    {
        return $authUser->can('Delete:VendorSale');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:VendorSale');
    }

    public function restore(AuthUser $authUser, VendorSale $vendorSale): bool
    {
        return $authUser->can('Restore:VendorSale');
    }

    public function forceDelete(AuthUser $authUser, VendorSale $vendorSale): bool
    {
        return $authUser->can('ForceDelete:VendorSale');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VendorSale');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VendorSale');
    }

    public function replicate(AuthUser $authUser, VendorSale $vendorSale): bool
    {
        return $authUser->can('Replicate:VendorSale');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VendorSale');
    }
}
