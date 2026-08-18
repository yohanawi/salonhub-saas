<?php

namespace App\Policies;

use App\Models\PayrollItem;
use App\Models\User;
use App\Services\BranchContext;

class PayrollItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->tenant_id !== null && ($user->can('payroll.view') || $user->can('payslips.view_own')));
    }

    public function view(User $user, PayrollItem $item): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $item->tenant_id) {
            return false;
        }

        if ($user->can('payroll.view')) {
            return $item->branch_id === null
                || app(BranchContext::class)->hasTenantWideBranchAccess($user)
                || $user->can('payroll.view_all_branches')
                || $user->branches()->whereKey($item->branch_id)->exists();
        }

        return $user->can('payslips.view_own')
            && $user->staffProfile
            && (int) $user->staffProfile->id === (int) $item->staff_id;
    }
}
