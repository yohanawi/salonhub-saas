<?php

namespace App\Policies;

use App\Models\CommissionPayout;
use App\Models\User;
use App\Services\BranchContext;

class CommissionPayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('commission_payouts.view'));
    }

    public function view(User $user, CommissionPayout $payout): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $payout->tenant_id || ! $user->can('commission_payouts.view')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->can('commissions.view_all_branches')
            || $user->branches()->whereKey($payout->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('commission_payouts.create'));
    }

    public function approve(User $user, CommissionPayout $payout): bool
    {
        return $this->view($user, $payout)
            && ($user->hasRole('Super Admin') || $user->can('commission_payouts.approve'));
    }

    public function pay(User $user, CommissionPayout $payout): bool
    {
        return $this->view($user, $payout)
            && ($user->hasRole('Super Admin') || $user->can('commission_payouts.pay'));
    }
}
