<?php

namespace App\Services\Expense;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(Tenant $tenant, array $data, User $user): Expense
    {
        return DB::transaction(function () use ($tenant, $data, $user) {
            $branch = Branch::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($data['branch_id']);

            $this->validateTenantRelations($tenant, $data);

            $totals = $this->totals($data);

            $expense = Expense::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'expense_number' => 'EXP-TMP-' . uniqid(),
                'category_id' => $data['category_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'expense_date' => $data['expense_date'],
                'description' => $data['description'],
                'reference' => $data['reference_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'amount' => $totals['total'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax'],
                'discount_amount' => $totals['discount'],
                'total_amount' => $totals['total'],
                'paid_amount' => 0,
                'balance_amount' => $totals['total'],
                'currency' => $tenant->currency ?: 'LKR',
                'payment_status' => Expense::PAYMENT_UNPAID,
                'approval_status' => Expense::APPROVAL_PENDING,
                'expense_status' => Expense::STATUS_CONFIRMED,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $expense->update([
                'expense_number' => $this->expenseNumber($expense),
            ]);

            return $expense->fresh(['branch', 'category', 'vendor', 'creator']);
        });
    }

    public function update(Expense $expense, array $data, User $user): Expense
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'expense' => 'Cancelled expenses cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($expense, $data, $user) {
            $this->validateTenantRelations($expense->tenant, $data);
            $totals = $this->totals($data);

            if ((float) $expense->paid_amount > $totals['total']) {
                throw ValidationException::withMessages([
                    'subtotal' => 'Expense total cannot be lower than the amount already paid.',
                ]);
            }

            $expense->update([
                'branch_id' => $data['branch_id'],
                'category_id' => $data['category_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'expense_date' => $data['expense_date'],
                'description' => $data['description'],
                'reference' => $data['reference_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'amount' => $totals['total'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax'],
                'discount_amount' => $totals['discount'],
                'total_amount' => $totals['total'],
                'notes' => $data['notes'] ?? null,
                'updated_by' => $user->id,
            ]);

            return app(ExpensePaymentService::class)->refreshPaymentStatus($expense);
        });
    }

    public function approve(Expense $expense, User $user): Expense
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'expense' => 'Cancelled expenses cannot be approved.',
            ]);
        }

        $expense->update([
            'approval_status' => Expense::APPROVAL_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'updated_by' => $user->id,
        ]);

        return $expense->fresh(['approvedBy']);
    }

    public function reject(Expense $expense, User $user): Expense
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'expense' => 'Cancelled expenses cannot be rejected.',
            ]);
        }

        $expense->update([
            'approval_status' => Expense::APPROVAL_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $user->id,
        ]);

        return $expense->fresh();
    }

    public function cancel(Expense $expense, string $reason, User $user): Expense
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'expense' => 'This expense is already cancelled.',
            ]);
        }

        $expense->update([
            'expense_status' => Expense::STATUS_CANCELLED,
            'cancel_reason' => $reason,
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
            'updated_by' => $user->id,
        ]);

        return $expense->fresh(['cancelledBy']);
    }

    private function validateTenantRelations(Tenant $tenant, array $data): void
    {
        if (! Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->whereKey($data['branch_id'])->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Select a branch from this salon.']);
        }

        if (! ExpenseCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->whereKey($data['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Select an expense category from this salon.']);
        }

        if (! empty($data['vendor_id']) && ! Vendor::withoutTenantScope()->where('tenant_id', $tenant->id)->whereKey($data['vendor_id'])->exists()) {
            throw ValidationException::withMessages(['vendor_id' => 'Select a vendor from this salon.']);
        }
    }

    private function totals(array $data): array
    {
        $subtotal = max(0, (float) $data['subtotal']);
        $tax = max(0, (float) ($data['tax_amount'] ?? 0));
        $discount = min($subtotal + $tax, max(0, (float) ($data['discount_amount'] ?? 0)));

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => max(0, $subtotal + $tax - $discount),
        ];
    }

    private function expenseNumber(Expense $expense): string
    {
        return 'EXP-' . $expense->expense_date->format('Y') . '-' . str_pad((string) $expense->id, 6, '0', STR_PAD_LEFT);
    }
}
