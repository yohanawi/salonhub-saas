<?php

namespace App\Http\Requests\Staff;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffCommissionSetting;
use App\Models\StaffTimeOff;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Staff::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->tenantId();

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('staff', 'employee_code')->where('tenant_id', $tenantId)->withoutTrashed()],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(Staff::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'job_title' => ['required', 'string', 'max:150'],
            'hire_date' => ['nullable', 'date'],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(Staff::STATUSES)],
            'is_bookable' => ['nullable', 'boolean'],
            'show_online' => ['nullable', 'boolean'],
            'commission_type' => ['nullable', Rule::in(StaffCommissionSetting::TYPES)],
            'commission_value' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'primary_branch_id' => ['nullable', 'integer'],
            'branches' => ['nullable', 'array'],
            'branches.*.enabled' => ['nullable', 'boolean'],
            'branches.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'services' => ['nullable', 'array'],
            'services.*.enabled' => ['nullable', 'boolean'],
            'services.*.custom_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'services.*.custom_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'services.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'schedules' => ['nullable', 'array'],
            'schedules.*.branch_id' => ['nullable', 'integer'],
            'schedules.*.day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'schedules.*.is_working' => ['nullable', 'boolean'],
            'schedules.*.start_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.end_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.title' => ['nullable', 'string', 'max:100'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.branch_id' => ['nullable', 'integer'],
            'breaks.*.day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'breaks.*.start_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.end_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.title' => ['nullable', 'string', 'max:100'],
            'time_off' => ['nullable', 'array'],
            'time_off.*.branch_id' => ['nullable', 'integer'],
            'time_off.*.start_datetime' => ['nullable', 'date'],
            'time_off.*.end_datetime' => ['nullable', 'date'],
            'time_off.*.type' => ['nullable', Rule::in(StaffTimeOff::TYPES)],
            'time_off.*.reason' => ['nullable', 'string', 'max:1000'],
            'time_off.*.status' => ['nullable', Rule::in(['approved', 'pending', 'rejected', 'cancelled'])],
            'commission_settings' => ['nullable', 'array'],
            'commission_settings.*.service_id' => ['nullable', 'integer'],
            'commission_settings.*.commission_type' => ['nullable', Rule::in(StaffCommissionSetting::TYPES)],
            'commission_settings.*.commission_value' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = $this->tenantId();

                if (! $tenantId) {
                    return;
                }

                $enabledBranchIds = $this->enabledIds('branches');

                if ($this->boolean('is_bookable') && empty($enabledBranchIds)) {
                    $validator->errors()->add('branches', 'Bookable staff must be assigned to at least one branch.');
                }

                $this->validateIdsBelongToTenant($validator, Branch::class, $enabledBranchIds, $tenantId, 'branches');
                $this->validateIdsBelongToTenant($validator, Service::class, $this->enabledIds('services'), $tenantId, 'services');

                $otherBranchIds = collect($this->input('schedules', []))->pluck('branch_id')
                    ->merge(collect($this->input('breaks', []))->pluck('branch_id'))
                    ->merge(collect($this->input('time_off', []))->pluck('branch_id'))
                    ->filter()->map(fn ($id) => (int) $id)->unique()->all();
                $this->validateIdsBelongToTenant($validator, Branch::class, $otherBranchIds, $tenantId, 'branches');

                $commissionServiceIds = collect($this->input('commission_settings', []))->pluck('service_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();
                $this->validateIdsBelongToTenant($validator, Service::class, $commissionServiceIds, $tenantId, 'commission_settings');

                if ($this->filled('user_id') && ! User::query()->where('tenant_id', $tenantId)->whereKey($this->integer('user_id'))->exists()) {
                    $validator->errors()->add('user_id', 'The selected login user does not belong to this salon.');
                }

                if ($this->filled('primary_branch_id') && ! in_array($this->integer('primary_branch_id'), $enabledBranchIds, true)) {
                    $validator->errors()->add('primary_branch_id', 'The primary branch must be one of the assigned branches.');
                }

                foreach ($this->input('schedules', []) as $index => $schedule) {
                    if (! empty($schedule['is_working']) && (empty($schedule['branch_id']) || empty($schedule['start_time']) || empty($schedule['end_time']))) {
                        $validator->errors()->add("schedules.{$index}", 'Working schedule rows require branch, start time, and end time.');
                    }

                    if (! empty($schedule['start_time']) && ! empty($schedule['end_time']) && $schedule['end_time'] <= $schedule['start_time']) {
                        $validator->errors()->add("schedules.{$index}.end_time", 'The schedule end time must be after the start time.');
                    }
                }

                foreach ($this->input('breaks', []) as $index => $break) {
                    $hasAnyValue = collect($break)->filter(fn ($value) => $value !== null && $value !== '' && $value !== '0')->isNotEmpty();

                    if ($hasAnyValue && (empty($break['branch_id']) || empty($break['day_of_week']) || empty($break['start_time']) || empty($break['end_time']))) {
                        $validator->errors()->add("breaks.{$index}", 'Break rows require branch, day, start time, and end time.');
                    }

                    if (! empty($break['start_time']) && ! empty($break['end_time']) && $break['end_time'] <= $break['start_time']) {
                        $validator->errors()->add("breaks.{$index}.end_time", 'The break end time must be after the start time.');
                    }
                }

                foreach ($this->input('time_off', []) as $index => $timeOff) {
                    $hasAnyValue = ! empty($timeOff['branch_id'])
                        || ! empty($timeOff['start_datetime'])
                        || ! empty($timeOff['end_datetime'])
                        || ! empty($timeOff['reason']);

                    if ($hasAnyValue && (empty($timeOff['start_datetime']) || empty($timeOff['end_datetime']))) {
                        $validator->errors()->add("time_off.{$index}", 'Time off rows require both start and end date/time.');
                    }

                    if (! empty($timeOff['start_datetime']) && ! empty($timeOff['end_datetime']) && strtotime($timeOff['end_datetime']) <= strtotime($timeOff['start_datetime'])) {
                        $validator->errors()->add("time_off.{$index}.end_datetime", 'The time off end date must be after the start date.');
                    }
                }
            },
        ];
    }

    protected function tenantId(): ?int
    {
        return $this->user()->hasRole('Super Admin')
            ? ($this->filled('tenant_id') ? $this->integer('tenant_id') : null)
            : $this->user()->tenant_id;
    }

    private function enabledIds(string $key): array
    {
        return collect($this->input($key, []))
            ->filter(fn (array $item) => ! empty($item['enabled']))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function validateIdsBelongToTenant(Validator $validator, string $model, array $ids, int $tenantId, string $field): void
    {
        if (! $ids) {
            return;
        }

        $validCount = $model::withoutTenantScope()->where('tenant_id', $tenantId)->whereIn('id', $ids)->count();

        if ($validCount !== count($ids)) {
            $validator->errors()->add($field, 'One or more selected records do not belong to this salon.');
        }
    }
}
