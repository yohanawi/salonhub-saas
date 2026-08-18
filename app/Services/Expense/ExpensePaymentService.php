<?php

namespace App\Services\Expense;

use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpensePaymentService
{
    public function record(Expense $expense, array $data, User $user): ExpensePayment
    {
        if ($expense->expense_status === Expense::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'expense' => 'Payments cannot be added to a cancelled expense.',
            ]);
        }

        $method = PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $expense->tenant_id)
            ->where('is_active', true)
            ->findOrFail($data['payment_method_id']);

        $amount = min((float) $data['amount'], (float) $expense->balance_amount);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a payment amount greater than zero.',
            ]);
        }

        if ($method->requires_reference && blank($data['reference_number'] ?? null)) {
            throw ValidationException::withMessages([
                'reference_number' => 'A reference number is required for this payment method.',
            ]);
        }

        return DB::transaction(function () use ($expense, $method, $data, $user, $amount) {
            $payment = ExpensePayment::create([
                'tenant_id' => $expense->tenant_id,
                'branch_id' => $expense->branch_id,
                'expense_id' => $expense->id,
                'payment_method_id' => $method->id,
                'amount' => $amount,
                'payment_method' => $method->code,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->refreshPaymentStatus($expense);

            return $payment->fresh(['paymentMethod', 'createdBy']);
        });
    }

    public function refreshPaymentStatus(Expense $expense): Expense
    {
        $paid = (float) $expense->payments()->sum('amount');
        $total = (float) $expense->total_amount;
        $balance = max(0, $total - $paid);

        $expense->update([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => match (true) {
                $paid <= 0 => Expense::PAYMENT_UNPAID,
                $paid < $total => Expense::PAYMENT_PARTIAL,
                default => Expense::PAYMENT_PAID,
            },
        ]);

        return $expense->fresh(['payments.paymentMethod']);
    }
}
