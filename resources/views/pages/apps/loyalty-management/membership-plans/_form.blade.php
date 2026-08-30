@csrf
@if ($isSuperAdmin)
    <div class="mb-8">
        <label class="form-label required fw-semibold">
            Tenant
        </label>
        <select name="tenant_id" class="form-select form-select-solid" required>
            <option value="">
                Select tenant
            </option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenant?->id) == $tenant->id)>
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>
    </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="symbol symbol-35px">
                <div class="symbol-label bg-light-primary">
                    <i class="bi bi-gem text-primary"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Plan Details
                </div>
                <div class="text-muted fs-8">
                    Name and position this membership offer.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <label class="form-label required fw-semibold">
            Plan Name
        </label>
        <input name="name" value="{{ old('name', $plan->name) }}" class="form-control form-control-solid"
            required>
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Code
        </label>
        <input name="code" value="{{ old('code', $plan->code) }}" class="form-control form-control-solid"
            required>
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold">
            Status
        </label>
        <select name="status" class="form-select form-select-solid">
            <option value="active" @selected(old('status', $plan->status) === 'active')>
                Active
            </option>
            <option value="inactive" @selected(old('status', $plan->status) === 'inactive')>
                Inactive
            </option>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">
            Description
        </label>
        <textarea name="description" class="form-control form-control-solid" rows="3">{{ old('description', $plan->description) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-check form-switch form-check-custom form-check-solid bg-light rounded px-5 py-4">
            <input type="checkbox" name="is_featured" value="1" class="form-check-input"
                @checked(old('is_featured', $plan->is_featured))>
            <span class="form-check-label fw-semibold text-gray-800">
                Featured plan
            </span>
        </label>
    </div>

    <div class="col-12">
        <div class="separator separator-dashed my-2"></div>
    </div>

    <div class="col-12">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="symbol symbol-35px">
                <div class="symbol-label bg-light-success">
                    <i class="bi bi-cash-stack text-success"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Pricing & Duration
                </div>
                <div class="text-muted fs-8">
                    Configure the amount charged and how long the membership stays active.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Price
        </label>
        <input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}"
            class="form-control form-control-solid" required>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Joining Fee
        </label>
        <input type="number" step="0.01" name="joining_fee" value="{{ old('joining_fee', $plan->joining_fee) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-2">
        <label class="form-label required fw-semibold">
            Duration
        </label>
        <input type="number" name="duration_value" value="{{ old('duration_value', $plan->duration_value) }}"
            class="form-control form-control-solid" required>
    </div>

    <div class="col-md-2">
        <label class="form-label required fw-semibold">
            Duration Type
        </label>
        <select name="duration_type" class="form-select form-select-solid">
            <option value="days" @selected(old('duration_type', $plan->duration_type) === 'days')>
                Days
            </option>
            <option value="months" @selected(old('duration_type', $plan->duration_type) === 'months')>
                Months
            </option>
            <option value="years" @selected(old('duration_type', $plan->duration_type) === 'years')>
                Years
            </option>
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label required fw-semibold">
            Billing
        </label>
        <select name="billing_type" class="form-select form-select-solid">
            <option value="one_time" @selected(old('billing_type', $plan->billing_type) === 'one_time')>
                One Time
            </option>
            <option value="recurring" @selected(old('billing_type', $plan->billing_type) === 'recurring')>
                Recurring
            </option>
        </select>
    </div>
</div>

<div class="separator separator-dashed my-8"></div>

<div class="d-flex align-items-center gap-3 mb-6">
    <div class="symbol symbol-35px">
        <div class="symbol-label bg-light-info">
            <i class="bi bi-percent text-info"></i>
        </div>
    </div>
    <div>
        <h4 class="fw-bold text-gray-900 mb-1">
            Benefits
        </h4>
        <div class="text-muted fs-8">
            Add discounts, free services or points multipliers included with this plan.
        </div>
    </div>
</div>

@php($existingBenefits = old('benefits', $plan->benefits?->toArray() ?: [['benefit_type' => 'service_discount', 'discount_type' => 'percentage', 'discount_value' => 10, 'priority' => 100, 'status' => 'active']]))
@for ($i = 0; $i < max(3, count($existingBenefits)); $i++)
    @php($benefit = $existingBenefits[$i] ?? [])
    <div class="border border-gray-200 rounded p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between gap-4 mb-4">
            <div class="fw-bold text-gray-900">
                Benefit {{ $i + 1 }}
            </div>
            <span class="badge badge-light-primary">
                Optional
            </span>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <label class="form-label fw-semibold fs-8">
                    Benefit Type
                </label>
                <select name="benefits[{{ $i }}][benefit_type]" class="form-select form-select-solid">
                    <option value="">
                        No benefit
                    </option>
                    @foreach(['service_discount' => 'Service Discount', 'product_discount' => 'Product Discount', 'free_service' => 'Free Service', 'bonus_points_multiplier' => 'Points Multiplier'] as $value => $label)
                        <option value="{{ $value }}" @selected(($benefit['benefit_type'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold fs-8">
                    Discount Type
                </label>
                <select name="benefits[{{ $i }}][discount_type]" class="form-select form-select-solid">
                    <option value="percentage" @selected(($benefit['discount_type'] ?? '') === 'percentage')>
                        Percentage
                    </option>
                    <option value="fixed" @selected(($benefit['discount_type'] ?? '') === 'fixed')>
                        Fixed
                    </option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold fs-8">
                    Discount
                </label>
                <input type="number" step="0.01" name="benefits[{{ $i }}][discount_value]"
                    value="{{ $benefit['discount_value'] ?? 0 }}" class="form-control form-control-solid"
                    placeholder="Discount">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold fs-8">
                    Multiplier
                </label>
                <input type="number" step="0.01" name="benefits[{{ $i }}][loyalty_multiplier]"
                    value="{{ $benefit['loyalty_multiplier'] ?? '' }}" class="form-control form-control-solid"
                    placeholder="Multiplier">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold fs-8">
                    Service
                </label>
                <select name="benefits[{{ $i }}][service_id]" class="form-select form-select-solid">
                    <option value="">
                        Any service
                    </option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @selected(($benefit['service_id'] ?? null) == $service->id)>
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label fw-semibold fs-8">
                    Priority
                </label>
                <input type="number" name="benefits[{{ $i }}][priority]"
                    value="{{ $benefit['priority'] ?? 100 }}" class="form-control form-control-solid">
            </div>
        </div>

        <input type="hidden" name="benefits[{{ $i }}][status]" value="{{ $benefit['status'] ?? 'active' }}">
    </div>
@endfor

<div class="d-flex justify-content-end gap-3 mt-8">
    <a href="{{ route('loyalty-management.membership-plans.index') }}" class="btn btn-light">
        Cancel
    </a>
    <button class="btn btn-primary">
        <i class="bi bi-check-circle-fill me-2"></i>
        Save Plan
    </button>
</div>
