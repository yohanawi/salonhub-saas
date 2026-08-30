<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Services\BranchContext;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('billing.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $invoice->tenant_id || ! $user->can('billing.view')) {
            return false;
        }

        if (app(BranchContext::class)->hasTenantWideBranchAccess($user) || $user->can('billing.view_all_branches')) {
            return true;
        }

        return $user->branches()->whereKey($invoice->branch_id)->exists();
    }

    public function checkout(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('billing.checkout');
    }

    public function create(User $user): bool
    {
        return $this->checkout($user);
    }

    public function void(User $user, Invoice $invoice): bool
    {
        if ($invoice->status === Invoice::STATUS_VOID) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $this->view($user, $invoice) && $user->can('invoices.void');
    }

    public function applyDiscount(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('billing.apply_discount');
    }

    public function viewReports(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('billing.view_reports'));
    }
}
