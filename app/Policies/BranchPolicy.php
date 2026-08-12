<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchContext;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null
            && (
                app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('branches.view')
                || $user->branches()->exists()
            );
    }

    public function view(User $user, Branch $branch): bool
    {
        return (
                app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('branches.view')
            )
            && app(BranchContext::class)->canAccess($user, $branch);
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null
            && (
                app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('branches.create')
            );
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $branch->tenant_id) {
            return false;
        }

        if (app(BranchContext::class)->hasTenantWideBranchAccess($user)) {
            return true;
        }

        return $user->can('branches.update')
            && $user->hasRole('Branch Manager')
            && $user->branches()->whereKey($branch->id)->exists();
    }

    public function changeStatus(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $branch->tenant_id
            && (
                app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('branches.change_status')
            );
    }

    public function manageHours(User $user, Branch $branch): bool
    {
        return $this->update($user, $branch)
            && (
                app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('branches.manage_hours')
            );
    }

    public function setMain(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $branch->tenant_id
            && (
                app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('branches.set_main')
            );
    }
}
