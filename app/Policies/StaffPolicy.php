<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;
use App\Services\BranchContext;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('staff.view');
    }

    public function view(User $user, Staff $staff): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $staff->tenant_id || ! $user->can('staff.view')) {
            return false;
        }

        if (app(BranchContext::class)->hasTenantWideBranchAccess($user)) {
            return true;
        }

        return $staff->user_id === $user->id
            || $staff->branches()->whereIn('branches.id', $user->branches()->pluck('branches.id'))->exists();
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null
            && app(BranchContext::class)->hasTenantWideBranchAccess($user)
            && $user->can('staff.create');
    }

    public function update(User $user, Staff $staff): bool
    {
        if ($staff->trashed()) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $staff->tenant_id
            && app(BranchContext::class)->hasTenantWideBranchAccess($user)
            && $user->can('staff.update');
    }

    public function delete(User $user, Staff $staff): bool
    {
        if ($user->hasRole('Super Admin')) {
            return $this->update($user, $staff);
        }

        return $this->update($user, $staff) && $user->can('staff.delete');
    }

    public function manageBranches(User $user, Staff $staff): bool
    {
        return $this->update($user, $staff) && ($user->hasRole('Super Admin') || $user->can('staff.manage_branches'));
    }

    public function manageServices(User $user, Staff $staff): bool
    {
        return $this->update($user, $staff) && ($user->hasRole('Super Admin') || $user->can('staff.manage_services'));
    }

    public function manageSchedule(User $user, Staff $staff): bool
    {
        return $this->update($user, $staff) && ($user->hasRole('Super Admin') || $user->can('staff.manage_schedule'));
    }

    public function manageTimeOff(User $user, Staff $staff): bool
    {
        return $this->update($user, $staff) && ($user->hasRole('Super Admin') || $user->can('staff.manage_time_off'));
    }

    public function manageCommission(User $user, Staff $staff): bool
    {
        return $this->update($user, $staff) && ($user->hasRole('Super Admin') || $user->can('staff.manage_commission'));
    }
}
