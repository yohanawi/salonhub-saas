<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Services\BranchContext;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $payment->tenant_id || ! $user->can('payments.view')) {
            return false;
        }

        if (app(BranchContext::class)->hasTenantWideBranchAccess($user) || $user->can('billing.view_all_branches')) {
            return true;
        }

        return $payment->branch_id
            ? $user->branches()->whereKey($payment->branch_id)->exists()
            : $payment->invoice && $user->branches()->whereKey($payment->invoice->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('payments.create');
    }
}
