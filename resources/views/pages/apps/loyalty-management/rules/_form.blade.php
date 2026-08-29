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
                    <i class="bi bi-diagram-3-fill text-primary"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Rule Identity
                </div>
                <div class="text-muted fs-8">
                    Connect this rule to a program and name the customer earning behavior.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label required fw-semibold">
            Program
        </label>
        <select name="loyalty_program_id" class="form-select form-select-solid" required>
            <option value="">
                Select program
            </option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" @selected(old('loyalty_program_id', $rule->loyalty_program_id) == $program->id)>
                    {{ $program->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label required fw-semibold">
            Rule Name
        </label>
        <input name="name" value="{{ old('name', $rule->name) }}" class="form-control form-control-solid"
            required>
    </div>

    <div class="col-12">
        <div class="separator separator-dashed my-2"></div>
    </div>

    <div class="col-12">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="symbol symbol-35px">
                <div class="symbol-label bg-light-success">
                    <i class="bi bi-calculator-fill text-success"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Earning Logic
                </div>
                <div class="text-muted fs-8">
                    Define how many points are awarded and when limits apply.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Type
        </label>
        <select name="rule_type" class="form-select form-select-solid">
            <option value="spend" @selected(old('rule_type', $rule->rule_type) === 'spend')>
                Spend
            </option>
            <option value="bonus" @selected(old('rule_type', $rule->rule_type) === 'bonus')>
                Bonus
            </option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Spend Amount
        </label>
        <input type="number" step="0.01" name="spend_amount"
            value="{{ old('spend_amount', $rule->spend_amount) }}" class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Points
        </label>
        <input type="number" name="points_awarded" value="{{ old('points_awarded', $rule->points_awarded) }}"
            class="form-control form-control-solid" required>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Minimum Purchase
        </label>
        <input type="number" step="0.01" name="minimum_purchase_amount"
            value="{{ old('minimum_purchase_amount', $rule->minimum_purchase_amount) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Maximum Points
        </label>
        <input type="number" name="maximum_points_per_transaction"
            value="{{ old('maximum_points_per_transaction', $rule->maximum_points_per_transaction) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Priority
        </label>
        <input type="number" name="priority" value="{{ old('priority', $rule->priority) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label required fw-semibold">
            Status
        </label>
        <select name="status" class="form-select form-select-solid">
            <option value="active" @selected(old('status', $rule->status) === 'active')>
                Active
            </option>
            <option value="inactive" @selected(old('status', $rule->status) === 'inactive')>
                Inactive
            </option>
        </select>
    </div>

    <div class="col-12">
        <div class="separator separator-dashed my-2"></div>
    </div>

    <div class="col-12">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="symbol symbol-35px">
                <div class="symbol-label bg-light-info">
                    <i class="bi bi-funnel-fill text-info"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-gray-900">
                    Scope & Schedule
                </div>
                <div class="text-muted fs-8">
                    Optionally limit the rule to a branch, service, product or date range.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Branch
        </label>
        <select name="branch_id" class="form-select form-select-solid">
            <option value="">
                All branches
            </option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $rule->branch_id) == $branch->id)>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Service
        </label>
        <select name="service_id" class="form-select form-select-solid">
            <option value="">
                Any service
            </option>
            @foreach($services as $service)
                <option value="{{ $service->id }}" @selected(old('service_id', $rule->service_id) == $service->id)>
                    {{ $service->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Product
        </label>
        <select name="product_id" class="form-select form-select-solid">
            <option value="">
                Any product
            </option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id', $rule->product_id) == $product->id)>
                    {{ $product->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            Start Date
        </label>
        <input type="date" name="start_date" value="{{ old('start_date', $rule->start_date?->toDateString()) }}"
            class="form-control form-control-solid">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">
            End Date
        </label>
        <input type="date" name="end_date" value="{{ old('end_date', $rule->end_date?->toDateString()) }}"
            class="form-control form-control-solid">
    </div>
</div>

<div class="d-flex justify-content-end gap-3 mt-8">
    <a href="{{ route('loyalty-management.rules.index') }}" class="btn btn-light">
        Cancel
    </a>
    <button class="btn btn-primary">
        <i class="bi bi-check-circle-fill me-2"></i>
        Save Rule
    </button>
</div>
