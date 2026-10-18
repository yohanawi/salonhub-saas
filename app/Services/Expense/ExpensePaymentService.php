<?php

namespace App\Services\Expense;

use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Billing\CashRegisterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpensePaymentService
{
    public function record(Expense $expense, array $data, User $user): ExpensePayment
    {
        $method = PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $expense->tenant_id)
            ->where('is_active', true)
            ->findOrFail($data['payment_method_id']);

        if ($method->requires_reference && blank($data['reference_number'] ?? null)) {
            throw ValidationException::withMessages([
                'reference_number' => 'A reference number is required for this payment method.',
            ]);
        }

        $fromDrawer = filter_var($data['from_cash_drawer'] ?? false, FILTER_VALIDATE_BOOL);

        if ($fromDrawer && $method->type !== PaymentMethod::TYPE_CASH) {
            throw ValidationException::withMessages([
                'from_cash_drawer' => 'Only cash payments can be taken from the cash drawer.',
            ]);
        }

        return DB::transaction(function () use ($expense, $method, $data, $user, $fromDrawer) {
            // Lock the expense so concurrent payments cannot both pass the balance check and overpay it.
            $expense = Expense::withoutTenantScope()->whereKey($expense->getKey())->lockForUpdate()->firstOrFail();

            if ($expense->expense_status === Expense::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'expense' => 'Payments cannot be added to a cancelled expense.',
                ]);
            }

            if ($expense->approval_status === Expense::APPROVAL_REJECTED) {
                throw ValidationException::withMessages([
                    'expense' => 'Payments cannot be added to a rejected expense.',
                ]);
            }

            $amount = min((float) $data['amount'], (float) $expense->balance_amount);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Enter a payment amount greater than zero.',
                ]);
            }

            // Cash paid out of the counter drawer reduces what the open shift should hold at close.
            $shift = $fromDrawer ? app(CashRegisterService::class)->shiftForDrawerExpense($expense->branch_id, $amount) : null;

            $payment = ExpensePayment::create([
                'tenant_id' => $expense->tenant_id,
                'branch_id' => $expense->branch_id,
                'expense_id' => $expense->id,
                'payment_method_id' => $method->id,
                'cash_register_shift_id' => $shift?->id,
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
