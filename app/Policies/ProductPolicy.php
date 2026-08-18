<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('product.view'));
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $product->tenant_id && $user->can('product.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('product.create'));
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $product->tenant_id && $user->can('product.update'));
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $product->tenant_id && $user->can('product.deactivate'));
    }
}
