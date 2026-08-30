<form method="POST" action="{{ $action }}">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    @php
        $oldFeatures = old('features');
        $selectedFeatures =
            $oldFeatures !== null
                ? $oldFeatures
                : collect($plan->features ?? [])
                    ->filter(fn($value) => !in_array($value, [false, null, 'false', 0, '0'], true))
                    ->keys()
                    ->all();
    @endphp

    {{-- Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center mb-7">
            <i class="bi bi-exclamation-circle-fill fs-2 me-3"></i>
            <div>
                <div class="fw-bold mb-1">
                    Please check the form
                </div>
                <div>
                    {{ $errors->first() }}
                </div>
            </div>
        </div>
    @endif

    <div class="row g-7">
        <div class="col-xl-8">
            {{-- Plan Information --}}
            <div class="card border-0 shadow-sm mb-7">
                <div class="card-header border-0 pt-7">
                    <div class="card-title d-flex align-items-center gap-3">
                        <div class="symbol symbol-45px">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-box-seam fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Plan Information
                            </h2>
                            <div class="text-muted fs-7">
                                Define the basic identity and pricing of this subscription plan.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="row g-5">
                        <div class="col-md-7">
                            <label class="form-label required fw-semibold">
                                Plan Name
                            </label>
                            <input type="text" name="name" value="{{ old('name', $plan->name) }}"
                                class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Professional"
                                required>
                            @error('name')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">
                                Slug
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-link-45deg"></i>
                                </span>
                                <input type="text" name="slug" id="plan-slug-input"
                                    value="{{ old('slug', $plan->slug) }}" class="form-control"
                                    placeholder="professional" autocomplete="off">
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Auto-generated from the plan name. Edit anytime to customize.
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="separator separator-dashed my-2"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required fw-semibold">
                                Price
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    LKR
                                </span>
                                <input type="number" name="price" value="{{ old('price', $plan->price ?? 0) }}"
                                    class="form-control @error('price') is-invalid @enderror" min="0"
                                    step="0.01" placeholder="0.00" required>
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Use 0 for a free plan.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required fw-semibold">
                                Billing Period
                            </label>
                            <select name="billing_period" class="form-select" required data-control="select2" data-hide-search="true">
                                @foreach ($billingPeriods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('billing_period', $plan->billing_period ?? 'monthly') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                Trial Period
                            </label>
                            <div class="input-group">
                                <input type="number" name="trial_days"
                                    value="{{ old('trial_days', $plan->trial_days ?? 0) }}" class="form-control"
                                    min="0" placeholder="14">
                                <span class="input-group-text">
                                    Days
                                </span>
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Use 0 to disable trial.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Usage Limits --}}
            <div class="card border-0 shadow-sm mb-7">
                <div class="card-header border-0 pt-7">
                    <div class="card-title d-flex align-items-center gap-3">
                        <div class="symbol symbol-45px">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-speedometer2 fs-2 text-info"></i>
                            </div>
                        </div>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Usage Limits
                            </h2>
                            <div class="text-muted fs-7">
                                Control how much each tenant can use under this plan.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="row g-5">
                        <div class="col-md-6 col-xl-3">
                            <label class="form-label fw-semibold">
                                Branches
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-building"></i>
                                </span>
                                <input type="number" name="max_branches"
                                    value="{{ old('max_branches', $plan->max_branches) }}" class="form-control"
                                    min="1" placeholder="∞">
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Empty = unlimited
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label class="form-label fw-semibold">
                                Staff
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-badge"></i>
                                </span>
                                <input type="number" name="max_staff" value="{{ old('max_staff', $plan->max_staff) }}"
                                    class="form-control" min="1" placeholder="∞">
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Empty = unlimited
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label class="form-label fw-semibold">
                                Users
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-people"></i>
                                </span>
                                <input type="number" name="max_users"
                                    value="{{ old('max_users', $plan->max_users) }}" class="form-control"
                                    min="1" placeholder="∞">
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Empty = unlimited
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label class="form-label fw-semibold">
                                Customers
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-heart"></i>
                                </span>
                                <input type="number" name="max_customers"
                                    value="{{ old('max_customers', $plan->max_customers) }}" class="form-control"
                                    min="1" placeholder="∞">
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                Empty = unlimited
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-7">
                    <div class="card-title d-flex align-items-center gap-3">
                        <div class="symbol symbol-45px">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-puzzle fs-2 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Plan Features
                            </h2>
                            <div class="text-muted fs-7">
                                Select which modules and capabilities are available.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="row g-4">
                        @foreach ($featureOptions as $key => $label)
                            <div class="col-md-6 col-xl-4">
                                <label class="feature-option border rounded-3 p-4 d-block h-100"
                                    for="feature_{{ $key }}">
                                    <div class="d-flex align-items-start">
                                        <div class="form-check form-check-custom form-check-solid me-3 mt-1">
                                            <input id="feature_{{ $key }}" class="form-check-input"
                                                type="checkbox" name="features[]" value="{{ $key }}"
                                                @checked(in_array($key, $selectedFeatures, true))>
                                        </div>

                                        <div>
                                            <div class="fw-bold text-gray-900">
                                                {{ $label }}
                                            </div>
                                            <div class="text-muted fs-8 mt-1">
                                                Enable this feature for tenants using this plan.
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================================
            RIGHT SETTINGS
        ======================================================= --}}
        <div class="col-xl-4">
            <div class="position-sticky" style="top: 100px;">
                <div class="card border-0 shadow-sm mb-7">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-sliders fs-3 text-warning"></i>
                                </div>
                            </div>
                            <h2 class="fw-bold mb-0">
                                Plan Settings
                            </h2>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        <div class="d-flex align-items-center justify-content-between border rounded p-4 mb-4">
                            <div>
                                <div class="fw-bold text-gray-900">
                                    Active Plan
                                </div>
                                <div class="text-muted fs-8">
                                    Allow tenants to select this plan.
                                </div>
                            </div>
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    @checked((bool) old('is_active', $plan->exists ? $plan->is_active : true))>
                            </label>
                        </div>
                        <div class="d-flex align-items-center justify-content-between border rounded p-4">
                            <div>
                                <div class="fw-bold text-gray-900">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    Recommended
                                </div>
                                <div class="text-muted fs-8">
                                    Highlight this plan to customers.
                                </div>
                            </div>
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_recommended" value="1"
                                    @checked((bool) old('is_recommended', $plan->is_recommended))>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-7">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title">
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Display Order
                                </h2>
                                <div class="text-muted fs-8">
                                    Control the order plans appear in pricing screens.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        <label class="form-label fw-semibold">
                            Sort Order
                        </label>
                        <input type="number" name="sort_order"
                            value="{{ old('sort_order', $plan->sort_order ?? 0) }}" class="form-control"
                            min="0">
                        <div class="text-muted fs-8 mt-2">
                            Lower values appear first.
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title">
                            <h2 class="fw-bold mb-0">
                                Plan Preview
                            </h2>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        <div class="border rounded-3 p-5 text-center">
                            <div class="symbol symbol-60px mb-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-box-seam fs-2 text-primary"></i>
                                </div>
                            </div>

                            <h3 id="preview_plan_name" class="fw-bold text-gray-900 mb-2">
                                {{ old('name', $plan->name ?: 'Plan Name') }}
                            </h3>

                            <div class="d-flex justify-content-center align-items-baseline gap-1">
                                <span class="text-muted fs-7">
                                    LKR
                                </span>
                                <span id="preview_plan_price" class="fw-bold fs-2 text-gray-900">
                                    {{ number_format((float) old('price', $plan->price ?? 0)) }}
                                </span>
                            </div>

                            <div class="text-muted fs-7 mt-1">
                                Subscription Plan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-7">
        <div class="card-body py-5">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-4">
                <div>
                    <div class="fw-semibold text-gray-900">
                        {{ $plan->exists ? 'Update Subscription Plan' : 'Create Subscription Plan' }}
                    </div>
                    <div class="text-muted fs-8">
                        Review pricing, limits and enabled features before saving.
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ $plan->exists ? route('plan-management.plans.show', $plan) : route('plan-management.plans.index') }}"
                        class="btn btn-light">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-7">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ $submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .card {
                border-radius: 14px;
            }

            .symbol-label {
                border-radius: 12px;
            }

            .feature-option {
                cursor: pointer;
                transition:
                    transform .2s ease,
                    border-color .2s ease,
                    background-color .2s ease;
            }

            .feature-option:hover {
                transform: translateY(-2px);
                border-color: var(--bs-primary) !important;
                background: var(--bs-gray-100);
            }

            .feature-option:has(input:checked) {
                border-color: var(--bs-primary) !important;
                background: var(--bs-primary-light);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const nameInput = document.querySelector('input[name="name"]');
                const priceInput = document.querySelector('input[name="price"]');
                const slugInput = document.querySelector('input[name="slug"]');
                const previewName = document.getElementById('preview_plan_name');
                const previewPrice = document.getElementById('preview_plan_price');

                function updatePreview() {
                    if (previewName && nameInput) {
                        previewName.textContent = nameInput.value.trim() || 'Plan Name';
                    }

                    if (previewPrice && priceInput) {
                        const price = Number(priceInput.value || 0);
                        previewPrice.textContent = new Intl.NumberFormat().format(price);
                    }
                }

                function slugify(value) {
                    return value
                        .toString()
                        .trim()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }

                // Auto-generate the slug from the name, unless the user has
                // already typed a slug of their own (existing plan or manual edit).
                let slugManuallyEdited = Boolean(slugInput?.value.trim());

                slugInput?.addEventListener('input', () => {
                    slugManuallyEdited = slugInput.value.trim() !== '';
                });

                nameInput?.addEventListener('input', () => {
                    updatePreview();

                    if (slugInput && !slugManuallyEdited) {
                        slugInput.value = slugify(nameInput.value);
                    }
                });

                priceInput?.addEventListener(
                    'input',
                    updatePreview
                );
                updatePreview();
            });
        </script>
    @endpush

</form>
