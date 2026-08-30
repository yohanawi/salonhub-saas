<?php

namespace App\Services\Payroll;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffSalaryStructure;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryStructureService
{
    public function create(Tenant $tenant, array $data, User $user): StaffSalaryStructure
    {
        $staff = $this->staff($tenant, $data['staff_id']);
        $branch = $this->branch($tenant, $data['branch_id'] ?? null);

        return DB::transaction(function () use ($tenant, $staff, $branch, $data, $user) {
            StaffSalaryStructure::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('staff_id', $staff->id)
                ->where('status', StaffSalaryStructure::STATUS_ACTIVE)
                ->whereNull('effective_to')
                ->whereDate('effective_from', '<', $data['effective_from'])
                ->update([
                    'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
                    'updated_by' => $user->id,
                ]);

            return StaffSalaryStructure::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch?->id,
                'staff_id' => $staff->id,
                'salary_type' => $data['salary_type'],
                'basic_salary' => $data['basic_salary'] ?? 0,
                'hourly_rate' => $data['hourly_rate'] ?? 0,
                'daily_rate' => $data['daily_rate'] ?? 0,
                'overtime_enabled' => (bool) ($data['overtime_enabled'] ?? false),
                'overtime_rate' => $data['overtime_rate'] ?? 0,
                'commission_enabled' => (bool) ($data['commission_enabled'] ?? false),
                'payroll_frequency' => $data['payroll_frequency'] ?? 'monthly',
                'payment_method' => $data['payment_method'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'status' => $data['status'] ?? StaffSalaryStructure::STATUS_ACTIVE,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function update(StaffSalaryStructure $structure, array $data, User $user): StaffSalaryStructure
    {
        $branch = $this->branch($structure->tenant, $data['branch_id'] ?? null);

        $structure->update([
            'branch_id' => $branch?->id,
            'salary_type' => $data['salary_type'],
            'basic_salary' => $data['basic_salary'] ?? 0,
            'hourly_rate' => $data['hourly_rate'] ?? 0,
            'daily_rate' => $data['daily_rate'] ?? 0,
            'overtime_enabled' => (bool) ($data['overtime_enabled'] ?? false),
            'overtime_rate' => $data['overtime_rate'] ?? 0,
            'commission_enabled' => (bool) ($data['commission_enabled'] ?? false),
            'payroll_frequency' => $data['payroll_frequency'] ?? 'monthly',
            'payment_method' => $data['payment_method'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_name' => $data['bank_account_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'status' => $data['status'] ?? StaffSalaryStructure::STATUS_ACTIVE,
            'updated_by' => $user->id,
        ]);

        return $structure->fresh(['staff', 'branch']);
    }

    private function staff(Tenant $tenant, int $staffId): Staff
    {
        return Staff::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($staffId);
    }

    private function branch(Tenant $tenant, mixed $branchId): ?Branch
    {
        if (! $branchId) {
            return null;
        }

        return Branch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($branchId);
    }
}
