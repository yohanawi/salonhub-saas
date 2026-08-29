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
            @foreach ($tenants as $tenant)
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
                    <i class="bi bi-card-heading text-primary"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Program Details
                </div>
                <div class="text-muted fs-8">
                    Name the program and decide whether it is available to customers.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <label class="form-label required fw-semibold">
            Program Name
        </label>
        <input name="name" value="{{ old('name', $program->name) }}" class="form-control form-control-solid"
            required>
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold">
            Status
        </label>
        <select name="status" class="form-select form-select-solid">
            <option value="active" @selected(old('status', $program->status) === 'active')>
                Active
            </option>
            <option value="inactive" @selected(old('status', $program->status) === 'inactive')>
                Inactive
            </option>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">
            Description
        </label>
        <textarea name="description" rows="3" class="form-control form-control-solid">{{ old('description', $program->description) }}</textarea>
    </div>

    <div class="col-12">
        <div class="separator separator-dashed my-2"></div>
    </div>

    <div class="col-12">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="symbol symbol-35px">
                <div class="symbol-label bg-light-success">
                    <i class="bi bi-stars text-success"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Redemption Settings
                </div>
                <div class="text-muted fs-8">
                    Control point expiry and redemption value.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Expiry Days
        </label>
        <input type="number" name="points_expiry_days"
            value="{{ old('points_expiry_days', $program->points_expiry_days) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Redeem Points
        </label>
        <input type="number" name="redemption_points"
            value="{{ old('redemption_points', $program->redemption_points) }}"
            class="form-control form-control-solid" required>
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Redeem Value
        </label>
        <input type="number" step="0.01" name="redemption_value"
            value="{{ old('redemption_value', $program->redemption_value) }}"
            class="form-control form-control-solid" required>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Minimum Points
        </label>
        <input type="number" name="minimum_redeem_points"
            value="{{ old('minimum_redeem_points', $program->minimum_redeem_points) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Max Redeem %
        </label>
        <input type="number" step="0.01" name="maximum_redeem_percentage"
            value="{{ old('maximum_redeem_percentage', $program->maximum_redeem_percentage) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-9">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-check form-switch form-check-custom form-check-solid bg-light rounded px-5 py-4 h-100">
                    <input type="checkbox" name="allow_partial_redemption" value="1" class="form-check-input"
                        @checked(old('allow_partial_redemption', $program->allow_partial_redemption))>
                    <span class="form-check-label fw-semibold text-gray-800">
                        Allow partial redemption
                    </span>
                </label>
            </div>

            <div class="col-md-6">
                <label class="form-check form-switch form-check-custom form-check-solid bg-light rounded px-5 py-4 h-100">
                    <input type="checkbox" name="allow_points_on_discounted_sales" value="1"
                        class="form-check-input" @checked(old('allow_points_on_discounted_sales', $program->allow_points_on_discounted_sales))>
                    <span class="form-check-label fw-semibold text-gray-800">
                        Allow points on discounted sales
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-3 mt-8">
    <a href="{{ route('loyalty-management.programs.index') }}" class="btn btn-light">
        Cancel
    </a>
    <button class="btn btn-primary">
        <i class="bi bi-check-circle-fill me-2"></i>
        Save Program
    </button>
</div>
