<?php

namespace App\Policies;

use App\Models\ProductBrand;
use App\Models\User;

class ProductBrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('product_brand.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('product_brand.create'));
    }

    public function update(User $user, ProductBrand $brand): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $brand->tenant_id && $user->can('product_brand.update'));
    }

    public function delete(User $user, ProductBrand $brand): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $brand->tenant_id && $user->can('product_brand.delete'));
    }
}
