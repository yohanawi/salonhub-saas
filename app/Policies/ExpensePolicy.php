<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Services\BranchContext;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('expenses.view'));
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $expense->tenant_id || ! $user->can('expenses.view')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->can('expenses.view_all_branches')
            || $user->branches()->whereKey($expense->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('expenses.create'));
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            return false;
        }

        return $this->view($user, $expense) && ($user->hasRole('Super Admin') || $user->can('expenses.update'));
    }

    public function cancel(User $user, Expense $expense): bool
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            return false;
        }

        return $this->view($user, $expense) && ($user->hasRole('Super Admin') || $user->can('expenses.cancel'));
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $this->view($user, $expense) && ($user->hasRole('Super Admin') || $user->can('expenses.approve'));
    }

    public function reject(User $user, Expense $expense): bool
    {
        return $this->approve($user, $expense);
    }

    public function pay(User $user, Expense $expense): bool
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED || $expense->balance_amount <= 0) {
            return false;
        }

        return $this->view($user, $expense) && ($user->hasRole('Super Admin') || $user->can('expenses.pay'));
    }
}
