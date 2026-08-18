<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\BranchContext;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('payroll.view'));
    }

    public function view(User $user, PayrollPeriod $period): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $period->tenant_id || ! $user->can('payroll.view')) {
            return false;
        }

        return $period->branch_id === null
            || app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->can('payroll.view_all_branches')
            || $user->branches()->whereKey($period->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('payroll.create'));
    }
}
