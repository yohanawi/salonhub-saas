<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\BranchContext;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('payroll.view'));
    }

    public function view(User $user, PayrollRun $run): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $run->tenant_id || ! $user->can('payroll.view')) {
            return false;
        }

        return $run->branch_id === null
            || app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->can('payroll.view_all_branches')
            || $user->branches()->whereKey($run->branch_id)->exists();
    }

    public function calculate(User $user, PayrollRun|PayrollPeriod|null $target = null): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('payroll.calculate'));
    }

    public function approve(User $user, PayrollRun $run): bool
    {
        return $this->view($user, $run) && ($user->hasRole('Super Admin') || $user->can('payroll.approve'));
    }

    public function reject(User $user, PayrollRun $run): bool
    {
        return $this->view($user, $run) && ($user->hasRole('Super Admin') || $user->can('payroll.reject'));
    }

    public function pay(User $user, PayrollRun $run): bool
    {
        return $this->view($user, $run) && ($user->hasRole('Super Admin') || $user->can('payroll.pay'));
    }
}
