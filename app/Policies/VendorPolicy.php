<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('vendors.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('vendors.create'));
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $vendor->tenant_id && $user->can('vendors.update'));
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $vendor->tenant_id && $user->can('vendors.delete'));
    }
}
