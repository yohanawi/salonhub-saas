<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('supplier.view'));
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $supplier->tenant_id && $user->can('supplier.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('supplier.manage'));
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $supplier->tenant_id && $user->can('supplier.manage'));
    }
}
