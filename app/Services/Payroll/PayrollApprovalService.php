<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PayrollApprovalService
{
    public function submitForReview(PayrollRun $run, User $user): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_CALCULATED) {
            throw ValidationException::withMessages(['run' => 'Only calculated payroll can be submitted for review.']);
        }

        $run->update(['status' => PayrollRun::STATUS_UNDER_REVIEW]);
        $run->period?->update(['status' => PayrollPeriod::STATUS_UNDER_REVIEW]);

        return $run->fresh();
    }

    public function approve(PayrollRun $run, User $user): PayrollRun
    {
        if (! in_array($run->status, [PayrollRun::STATUS_CALCULATED, PayrollRun::STATUS_UNDER_REVIEW], true)) {
            throw ValidationException::withMessages(['run' => 'Only calculated payroll can be approved.']);
        }

        $run->update([
            'status' => PayrollRun::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $run->items()->update([
            'status' => PayrollItem::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $run->period?->update([
            'status' => PayrollPeriod::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $run->fresh(['items']);
    }

    public function reject(PayrollRun $run, User $user, ?string $notes = null): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_UNDER_REVIEW) {
            throw ValidationException::withMessages(['run' => 'Only payroll under review can be rejected.']);
        }

        $run->update([
            'status' => PayrollRun::STATUS_CALCULATED,
            'notes' => $notes,
        ]);

        $run->period?->update(['status' => PayrollPeriod::STATUS_CALCULATED]);

        return $run->fresh();
    }
}
