<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\BranchContext;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('service_categories.view');
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null
            && app(BranchContext::class)->hasTenantWideBranchAccess($user)
            && $user->can('service_categories.create');
    }

    public function update(User $user, ServiceCategory $category): bool
    {
        if ($category->trashed()) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $category->tenant_id
            && app(BranchContext::class)->hasTenantWideBranchAccess($user)
            && $user->can('service_categories.update');
    }

    public function delete(User $user, ServiceCategory $category): bool
    {
        if ($user->hasRole('Super Admin')) {
            return $this->update($user, $category);
        }

        return $this->update($user, $category)
            && $user->can('service_categories.delete');
    }
}
