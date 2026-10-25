<?php

namespace App\Services\Payroll;

use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Requested -> Approved -> Paid (cash out to the staff member) -> Recovered.
 * Recovery happens in payroll: PayrollCalculationService deducts outstanding advances and
 * PayrollPaymentService reduces the outstanding balance when that payroll is paid.
 */
class SalaryAdvanceService
{
    public function request(Staff $staff, array $data, User $user): SalaryAdvance
    {
        return DB::transaction(function () use ($staff, $data, $user) {
            $installments = ($data['repayment_type'] ?? SalaryAdvance::REPAYMENT_ONE_TIME) === SalaryAdvance::REPAYMENT_INSTALLMENTS;

            $advance = SalaryAdvance::withoutTenantScope()->create([
                'tenant_id' => $staff->tenant_id,
                'branch_id' => $data['branch_id'] ?? $staff->primaryBranch()?->id,
                'staff_id' => $staff->id,
                'requested_amount' => $data['amount'],
                'repayment_type' => $installments ? SalaryAdvance::REPAYMENT_INSTALLMENTS : SalaryAdvance::REPAYMENT_ONE_TIME,
                'installment_amount' => $installments ? $data['installment_amount'] : null,
                'request_date' => $data['request_date'] ?? today()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'status' => SalaryAdvance::STATUS_REQUESTED,
                'requested_by' => $user->id,
            ]);

            $advance->update(['advance_number' => 'ADV-' . now()->format('Y') . '-' . str_pad((string) $advance->id, 6, '0', STR_PAD_LEFT)]);

            return $advance;
        });
    }

    public function approve(SalaryAdvance $advance, User $user, ?float $amount = null): SalaryAdvance
    {
        $this->ensureStatus($advance, [SalaryAdvance::STATUS_REQUESTED], 'Only requested advances can be approved.');

        $approved = round($amount ?? (float) $advance->requested_amount, 2);

        if ($approved <= 0 || $approved > (float) $advance->requested_amount) {
            throw ValidationException::withMessages(['approved_amount' => 'The approved amount must be more than zero and not exceed the amount requested.']);
        }

        $advance->update([
            'approved_amount' => $approved,
            'approved_date' => today(),
            'approved_by' => $user->id,
            'status' => SalaryAdvance::STATUS_APPROVED,
        ]);

        return $advance;
    }

    public function reject(SalaryAdvance $advance, User $user, ?string $reason): SalaryAdvance
    {
        $this->ensureStatus($advance, [SalaryAdvance::STATUS_REQUESTED, SalaryAdvance::STATUS_APPROVED], 'Only unpaid advances can be rejected.');

        $advance->update([
            'status' => SalaryAdvance::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'approved_by' => $user->id,
        ]);

        return $advance;
    }

    /**
     * The cash has been handed over: from now on the full amount is outstanding and recovered in payroll.
     */
    public function markPaid(SalaryAdvance $advance, User $user, array $data = []): SalaryAdvance
    {
        $this->ensureStatus($advance, [SalaryAdvance::STATUS_APPROVED], 'Only approved advances can be paid out.');

        $amount = (float) $advance->approved_amount;

        $advance->update([
            'paid_amount' => $amount,
            'outstanding_amount' => $amount,
            'recovered_amount' => 0,
            'paid_date' => $data['paid_date'] ?? today()->toDateString(),
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'paid_by' => $user->id,
            'status' => SalaryAdvance::STATUS_PAID,
        ]);

        return $advance;
    }

    private function ensureStatus(SalaryAdvance $advance, array $statuses, string $message): void
    {
        if (! in_array($advance->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
