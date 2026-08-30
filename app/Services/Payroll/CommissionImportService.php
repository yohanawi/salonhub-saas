<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\Staff;
use App\Models\StaffCommission;
use Illuminate\Support\Collection;

class CommissionImportService
{
    public function approvedUnpaidForPeriod(Staff $staff, PayrollPeriod $period): Collection
    {
        return StaffCommission::withoutTenantScope()
            ->where('tenant_id', $period->tenant_id)
            ->where('staff_id', $staff->id)
            ->where('status', StaffCommission::STATUS_APPROVED)
            ->whereNull('paid_at')
            ->whereBetween('earned_at', [$period->start_date->startOfDay(), $period->end_date->endOfDay()])
            ->when($period->branch_id, fn ($query) => $query->where('branch_id', $period->branch_id))
            ->get();
    }
}
