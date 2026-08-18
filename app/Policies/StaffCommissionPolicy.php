<?php

namespace App\Policies;

use App\Models\StaffCommission;
use App\Models\User;
use App\Services\BranchContext;

class StaffCommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->tenant_id !== null && ($user->can('commissions.view') || $user->can('commissions.view_own')));
    }

    public function view(User $user, StaffCommission $commission): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $commission->tenant_id) {
            return false;
        }

        if ($user->can('commissions.view')) {
            return app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('commissions.view_all_branches')
                || $user->branches()->whereKey($commission->branch_id)->exists();
        }

        return $user->can('commissions.view_own')
            && $user->staffProfile
            && (int) $user->staffProfile->id === (int) $commission->staff_id;
    }

    public function approve(User $user, StaffCommission $commission): bool
    {
        return $this->view($user, $commission)
            && ($user->hasRole('Super Admin') || $user->can('commissions.approve'));
    }

    public function reject(User $user, StaffCommission $commission): bool
    {
        return $this->approve($user, $commission);
    }
}
