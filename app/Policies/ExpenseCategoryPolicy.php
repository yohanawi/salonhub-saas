<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('expense_categories.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('expense_categories.create'));
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $category->tenant_id && $user->can('expense_categories.update'));
    }

    public function delete(User $user, ExpenseCategory $category): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $category->tenant_id && $user->can('expense_categories.delete'));
    }
}
