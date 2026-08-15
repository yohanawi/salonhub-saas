<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Services\BranchContext;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('customer.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $customer->tenant_id || ! $user->can('customer.view')) {
            return false;
        }

        if (! $customer->branch_id || app(BranchContext::class)->hasTenantWideBranchAccess($user)) {
            return true;
        }

        return $user->branches()->whereKey($customer->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('customer.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($customer->trashed()) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $customer->tenant_id
            && $user->can('customer.update');
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($user->hasRole('Super Admin')) {
            return $this->update($user, $customer);
        }

        return $this->update($user, $customer) && $user->can('customer.delete');
    }

    public function viewNotes(User $user, Customer $customer): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $this->view($user, $customer) && $user->can('customer.view_notes');
    }
}
