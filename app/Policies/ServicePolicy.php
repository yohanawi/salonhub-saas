<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Services\BranchContext;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('services.view');
    }

    public function view(User $user, Service $service): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $service->tenant_id
            && $user->can('services.view');
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null
            && app(BranchContext::class)->hasTenantWideBranchAccess($user)
            && $user->can('services.create');
    }

    public function update(User $user, Service $service): bool
    {
        if ($service->trashed()) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $service->tenant_id
            && app(BranchContext::class)->hasTenantWideBranchAccess($user)
            && $user->can('services.update');
    }

    public function changeStatus(User $user, Service $service): bool
    {
        if ($user->hasRole('Super Admin')) {
            return $this->update($user, $service);
        }

        return $this->update($user, $service)
            && $user->can('services.change_status');
    }
}
