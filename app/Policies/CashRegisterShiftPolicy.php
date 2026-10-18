<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\CashRegisterShift;
use App\Models\User;
use App\Services\BranchContext;

/**
 * Anyone who takes payments at a branch runs its drawer; managers with billing reports can review shifts.
 */
class CashRegisterShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null && ($user->can('payments.create') || $user->can('billing.view_reports'));
    }

    public function view(User $user, CashRegisterShift $shift): bool
    {
        return $this->viewAny($user)
            && (int) $user->tenant_id === (int) $shift->tenant_id
            && app(BranchContext::class)->canAccess($user, $shift->branch);
    }

    public function open(User $user, Branch $branch): bool
    {
        return $user->tenant_id !== null
            && (int) $user->tenant_id === (int) $branch->tenant_id
            && $user->can('payments.create')
            && app(BranchContext::class)->canAccess($user, $branch);
    }

    public function manage(User $user, CashRegisterShift $shift): bool
    {
        return $shift->isOpen() && $this->open($user, $shift->branch);
    }
}
