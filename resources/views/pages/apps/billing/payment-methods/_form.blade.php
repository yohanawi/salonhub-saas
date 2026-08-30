@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-8">
        <i class="bi bi-exclamation-triangle-fill fs-2 me-4"></i>
        <div>
            <div class="fw-bold mb-1">Please check the form</div>
            <div>{{ $errors->first() }}</div>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="d-flex align-items-center gap-4">
                <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-primary flex-shrink-0"
                    style="width: 48px; height: 48px;">
                    <i class="bi bi-wallet2 text-primary fs-2"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-900 mb-1">
                        Payment Method Details
                    </h3>
                    <div class="text-muted fs-7">
                        Configure how this payment option behaves during POS checkout.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body pt-6 p-lg-8">
        <div class="row g-7">
            @if (($tenants ?? collect())->isNotEmpty())
                <div class="col-12">
                    <div class="rounded-4 bg-light-primary p-5">
                        <div class="d-flex align-items-start gap-4">
                            <div class="d-flex align-items-center justify-content-center rounded-3 bg-white flex-shrink-0"
                                style="width: 44px; height: 44px;">
                                <i class="bi bi-shop text-primary fs-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label required fw-semibold text-gray-800">
                                    Salon / Tenant
                                </label>
                                <select name="tenant_id"
                                    class="form-select form-select-solid @error('tenant_id') is-invalid @enderror"
                                    data-control="select2" data-placeholder="Select salon" required>
                                    <option value="">Select salon</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tenant_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="text-muted fs-8 mt-2">
                                    Choose the salon that owns this payment method.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-lg-6">
                <label class="form-label required fw-semibold text-gray-800">
                    Method Name
                </label>
                <div class="position-relative">
                    <i class="bi bi-credit-card-2-front position-absolute text-muted fs-5"
                        style="top: 50%; left: 16px; transform: translateY(-50%);"></i>
                    <input type="text" name="name" value="{{ old('name', $paymentMethod->name) }}"
                        class="form-control form-control-solid ps-12 @error('name') is-invalid @enderror" required
                        placeholder="e.g. Cash, Visa Card">
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="text-muted fs-8 mt-2">
                    Customer-facing name shown during checkout.
                </div>
            </div>

            <div class="col-lg-6">
                <label class="form-label required fw-semibold text-gray-800">
                    Method Code
                </label>
                <div class="position-relative">
                    <i class="bi bi-hash position-absolute text-muted fs-5"
                        style="top: 50%; left: 16px; transform: translateY(-50%);"></i>
                    <input type="text" name="code" value="{{ old('code', $paymentMethod->code) }}"
                        class="form-control form-control-solid ps-12 font-monospace @error('code') is-invalid @enderror"
                        required placeholder="e.g. CASH">
                    @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="text-muted fs-8 mt-2">
                    Internal identifier used by billing and reporting.
                </div>
            </div>

            <div class="col-lg-6">
                <label class="form-label required fw-semibold text-gray-800">
                    Payment Type
                </label>
                <select name="type" class="form-select form-select-solid @error('type') is-invalid @enderror"
                    data-control="select2" data-hide-search="true" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $paymentMethod->type) === $type)>
                            {{ str($type)->replace('_', ' ')->headline() }}
                        </option>
                    @endforeach
                </select>
                @error('type')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
                <div class="text-muted fs-8 mt-2">
                    Determines how the payment is classified inside the POS.
                </div>
            </div>

            <div class="col-lg-6">
                <label class="form-label fw-semibold text-gray-800">
                    Sort Order
                </label>
                <div class="position-relative">
                    <i class="bi bi-sort-numeric-down position-absolute text-muted fs-5"
                        style="top: 50%; left: 16px; transform: translateY(-50%);"></i>
                    <input type="number" name="sort_order"
                        value="{{ old('sort_order', $paymentMethod->sort_order ?? 0) }}"
                        class="form-control form-control-solid ps-12 @error('sort_order') is-invalid @enderror"
                        min="0" placeholder="0">
                    @error('sort_order')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="text-muted fs-8 mt-2">
                    Lower numbers appear first during checkout.
                </div>
            </div>

            <div class="col-12">
                <div class="separator separator-dashed my-2"></div>
                <div class="mb-5 mt-6">
                    <h4 class="fw-bold text-gray-900 mb-1">
                        Method Settings
                    </h4>
                    <div class="text-muted fs-7">
                        Control availability and transaction requirements.
                    </div>
                </div>
                <div class="row g-5">
                    <div class="col-lg-6">
                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer">
                            <div class="d-flex align-items-center gap-4">
                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-success"
                                    style="width: 44px; height: 44px;">
                                    <i class="bi bi-check-circle text-success fs-3"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-gray-900">
                                        Active
                                    </div>
                                    <div class="text-muted fs-8 mt-1">
                                        Allow staff to use this payment method at checkout.
                                    </div>
                                </div>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid ms-4">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    @checked(old('is_active', $paymentMethod->is_active ?? true))>
                            </div>
                        </label>
                    </div>

                    <div class="col-lg-6">
                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer">
                            <div class="d-flex align-items-center gap-4">
                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-warning"
                                    style="width: 44px; height: 44px;">
                                    <i class="bi bi-hash text-warning fs-3"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-gray-900">
                                        Requires Reference
                                    </div>
                                    <div class="text-muted fs-8 mt-1">
                                        Require a transaction or confirmation reference.
                                    </div>
                                </div>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid ms-4">
                                <input class="form-check-input" type="checkbox" name="requires_reference"
                                    value="1" @checked(old('requires_reference', $paymentMethod->requires_reference ?? false))>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="separator separator-dashed my-8"></div>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-4">
            <div class="text-muted fs-8">
                <i class="bi bi-info-circle me-1"></i>
                Required fields must be completed before saving.
            </div>
            <div class="d-flex gap-3">
                <a href="{{ route('billing.payment-methods.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>
                    Cancel
                </a>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check2-circle me-2"></i>
                    Save Payment Method
                </button>
            </div>
        </div>
    </div>
</div>
