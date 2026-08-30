<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('product_category.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('product_category.create'));
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $category->tenant_id && $user->can('product_category.update'));
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $category->tenant_id && $user->can('product_category.delete'));
    }
}
