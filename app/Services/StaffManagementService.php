<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class StaffManagementService
{
    public function create(Tenant $tenant, array $data): Staff
    {
        return DB::transaction(function () use ($tenant, $data) {
            $staff = $tenant->staff()->create($this->staffPayload($data));

            $this->syncBranches($staff, $tenant, $data['branches'] ?? [], $data['primary_branch_id'] ?? null);
            $this->syncServices($staff, $tenant, $data['services'] ?? []);
            $this->syncSchedules($staff, $tenant, $data['schedules'] ?? []);
            $this->syncBreaks($staff, $tenant, $data['breaks'] ?? []);
            $this->syncTimeOff($staff, $tenant, $data['time_off'] ?? []);
            $this->syncCommissionSettings($staff, $tenant, $data['commission_settings'] ?? []);

            return $staff->fresh(['branches', 'services', 'schedules', 'breaks', 'timeOff', 'commissionSettings']);
        });
    }

    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data) {
            $staff->update($this->staffPayload($data));

            $this->syncBranches($staff, $staff->tenant, $data['branches'] ?? [], $data['primary_branch_id'] ?? null);
            $this->syncServices($staff, $staff->tenant, $data['services'] ?? []);
            $this->syncSchedules($staff, $staff->tenant, $data['schedules'] ?? []);
            $this->syncBreaks($staff, $staff->tenant, $data['breaks'] ?? []);
            $this->syncTimeOff($staff, $staff->tenant, $data['time_off'] ?? []);
            $this->syncCommissionSettings($staff, $staff->tenant, $data['commission_settings'] ?? []);

            return $staff->fresh(['branches', 'services', 'schedules', 'breaks', 'timeOff', 'commissionSettings']);
        });
    }

    public function deactivate(Staff $staff): void
    {
        $staff->update([
            'status' => Staff::STATUS_INACTIVE,
            'is_bookable' => false,
            'show_online' => false,
        ]);
    }

    private function staffPayload(array $data): array
    {
        return [
            'user_id' => $data['user_id'] ?? null,
            'employee_code' => $data['employee_code'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'job_title' => $data['job_title'],
            'hire_date' => $data['hire_date'] ?? null,
            'profile_photo' => $data['profile_photo'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_bookable' => (bool) ($data['is_bookable'] ?? false),
            'show_online' => (bool) ($data['show_online'] ?? false),
            'status' => $data['status'] ?? Staff::STATUS_ACTIVE,
            'commission_type' => $data['commission_type'] ?? 'percentage',
            'commission_value' => $data['commission_value'] ?? 0,
        ];
    }

    private function syncBranches(Staff $staff, Tenant $tenant, array $branches, mixed $primaryBranchId): void
    {
        $allowed = Branch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', array_keys($branches))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $primaryBranchId = (int) $primaryBranchId;
        $pivot = [];

        foreach ($branches as $branchId => $branchData) {
            $branchId = (int) $branchId;

            if (! in_array($branchId, $allowed, true) || empty($branchData['enabled'])) {
                continue;
            }

            $pivot[$branchId] = [
                'tenant_id' => $tenant->id,
                'is_primary' => $branchId === $primaryBranchId,
                'status' => $branchData['status'] ?? 'active',
            ];
        }

        if ($pivot && ! collect($pivot)->contains(fn (array $data) => (bool) $data['is_primary'])) {
            $firstBranchId = array_key_first($pivot);
            $pivot[$firstBranchId]['is_primary'] = true;
        }

        $staff->branches()->sync($pivot);
    }

    private function syncServices(Staff $staff, Tenant $tenant, array $services): void
    {
        $allowed = Service::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', array_keys($services))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $pivot = [];

        foreach ($services as $serviceId => $serviceData) {
            $serviceId = (int) $serviceId;

            if (! in_array($serviceId, $allowed, true) || empty($serviceData['enabled'])) {
                continue;
            }

            $pivot[$serviceId] = [
                'tenant_id' => $tenant->id,
                'custom_duration_minutes' => $serviceData['custom_duration_minutes'] ?? null,
                'custom_price' => $serviceData['custom_price'] ?? null,
                'status' => $serviceData['status'] ?? 'active',
            ];
        }

        $staff->services()->sync($pivot);
    }

    private function syncSchedules(Staff $staff, Tenant $tenant, array $schedules): void
    {
        $staff->schedules()->delete();

        foreach ($schedules as $schedule) {
            if (empty($schedule['is_working']) || empty($schedule['branch_id'])) {
                continue;
            }

            $staff->schedules()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $schedule['branch_id'],
                'title' => $schedule['title'] ?? null,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_working' => true,
            ]);
        }
    }

    private function syncBreaks(Staff $staff, Tenant $tenant, array $breaks): void
    {
        $staff->breaks()->delete();

        foreach ($breaks as $break) {
            if (empty($break['branch_id']) || empty($break['day_of_week']) || empty($break['start_time']) || empty($break['end_time'])) {
                continue;
            }

            $staff->breaks()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $break['branch_id'],
                'day_of_week' => $break['day_of_week'],
                'start_time' => $break['start_time'],
                'end_time' => $break['end_time'],
                'title' => $break['title'] ?? null,
            ]);
        }
    }

    private function syncTimeOff(Staff $staff, Tenant $tenant, array $timeOff): void
    {
        $staff->timeOff()->delete();

        foreach ($timeOff as $item) {
            if (empty($item['start_datetime']) || empty($item['end_datetime'])) {
                continue;
            }

            $staff->timeOff()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $item['branch_id'] ?? null,
                'start_datetime' => $item['start_datetime'],
                'end_datetime' => $item['end_datetime'],
                'type' => $item['type'] ?? 'unavailable',
                'reason' => $item['reason'] ?? null,
                'status' => $item['status'] ?? 'approved',
            ]);
        }
    }

    private function syncCommissionSettings(Staff $staff, Tenant $tenant, array $settings): void
    {
        $staff->commissionSettings()->delete();

        foreach ($settings as $setting) {
            if (! isset($setting['commission_value']) || $setting['commission_value'] === '') {
                continue;
            }

            $staff->commissionSettings()->create([
                'tenant_id' => $tenant->id,
                'service_id' => $setting['service_id'] ?? null,
                'commission_type' => $setting['commission_type'] ?? 'percentage',
                'commission_value' => $setting['commission_value'],
            ]);
        }
    }
}
