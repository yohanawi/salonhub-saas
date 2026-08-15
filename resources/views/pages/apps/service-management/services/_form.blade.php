@php
    $isEdit = $service->exists;
@endphp

{{-- Validation Alert --}}
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">
        <div class="symbol symbol-45px me-4 flex-shrink-0">
            <span class="symbol-label bg-light-danger rounded-circle">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
            </span>
        </div>
        <div>
            <div class="fw-bold fs-6 text-gray-900 mb-1">
                Please check the service details
            </div>
            <div class="text-gray-700">
                {{ $errors->first() }}
            </div>
        </div>
    </div>
@endif

<div class="row g-8">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-8 overflow-hidden">
            <div class="card-header border-0 pt-8 pb-4">
                <div class="card-title align-items-start flex-column">
                    <div class="d-flex align-items-center mb-2">
                        <span class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-primary">
                                <i class="bi bi-scissors fs-2 text-primary"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Service Information
                            </h2>
                            <div class="text-muted fs-7">
                                Define the main details customers and staff will see.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="row g-6">
                    @if (($tenants ?? collect())->isNotEmpty())
                        <div class="col-12">
                            <label class="form-label required fw-semibold">
                                Salon / Tenant
                            </label>
                            <select name="tenant_id" id="service_tenant_id" data-control="select2"
                                data-hide-search="true"
                                class="form-select form-select-solid @error('tenant_id') is-invalid @enderror" required>
                                <option value="">
                                    Select salon
                                </option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>
                                        {{ $tenant->name }}
                                        @if ($tenant->email)
                                            — {{ $tenant->email }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="d-flex align-items-center text-muted fs-8 mt-2">
                                <i class="bi bi-info-circle me-2"></i>
                                Select a salon to load its categories and branches.
                            </div>
                            @error('tenant_id')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    @endif

                    <div class="col-lg-8">
                        <label class="form-label required fw-semibold">
                            Service Name
                        </label>
                        <div class="position-relative">
                            <span class="position-absolute top-50 translate-middle-y ms-4 text-muted">
                                <i class="bi bi-stars fs-5"></i>
                            </span>
                            <input type="text" name="name" value="{{ old('name', $service->name) }}"
                                class="form-control form-control-solid ps-11 @error('name') is-invalid @enderror"
                                placeholder="e.g. Signature Haircut" required>
                        </div>
                        @error('name')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">
                            Category
                        </label>
                        <select name="category_id" data-control="select2" data-hide-search="true"
                            class="form-select form-select-solid @error('category_id') is-invalid @enderror">
                            <option value="">
                                No category
                            </option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $service->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Description
                        </label>
                        <textarea name="description" class="form-control form-control-solid" rows="5"
                            placeholder="Briefly describe what this service includes...">{{ old('description', $service->description) }}</textarea>
                        <div class="text-muted fs-8 mt-2">
                            Keep it short and useful for staff and future online booking.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8 pb-4">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <span class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-info">
                                <i class="bi bi-shop fs-2 text-info"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Branch Availability
                            </h2>
                            <div class="text-muted fs-7">
                                Choose where this service is offered and configure local overrides.
                            </div>
                        </div>
                    </div>
                </div>

                @if ($branches->isNotEmpty())
                    <div class="card-toolbar">
                        <span class="badge badge-light-primary px-4 py-3">
                            {{ $branches->count() }}
                            {{ Str::plural('Branch', $branches->count()) }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="card-body pt-5">
                <div class="row g-5">
                    @forelse ($branches as $branch)
                        @php
                            $config = $branchConfigs->get($branch->id);
                            $enabled = old("branches.{$branch->id}.enabled", $config ? 1 : 0);
                            $active = old("branches.{$branch->id}.is_active", $config?->pivot?->is_active ?? true);
                            $price = old("branches.{$branch->id}.price", $config?->pivot?->price);
                            $duration = old(
                                "branches.{$branch->id}.duration_minutes",
                                $config?->pivot?->duration_minutes,
                            );
                        @endphp

                        <div class="col-12">
                            <div class="service-branch-card border rounded-3 p-5 {{ $enabled ? 'is-enabled' : '' }}"
                                data-branch-card>
                                <input type="hidden" name="branches[{{ $branch->id }}][enabled]" value="0">
                                <input type="hidden" name="branches[{{ $branch->id }}][is_active]" value="0">

                                <div
                                    class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 mb-5">
                                    <div class="d-flex align-items-center">
                                        <span class="symbol symbol-45px me-4">
                                            <span class="symbol-label bg-light-primary rounded-circle">
                                                <i class="bi bi-building fs-3 text-primary"></i>
                                            </span>
                                        </span>
                                        <div>
                                            <label class="form-check form-check-custom form-check-solid mb-1">
                                                <input class="form-check-input branch-enabled-checkbox" type="checkbox"
                                                    name="branches[{{ $branch->id }}][enabled]" value="1"
                                                    @checked($enabled)>
                                                <span class="form-check-label fw-bold text-gray-900 fs-6">
                                                    {{ $branch->name }}
                                                </span>
                                            </label>
                                            <div class="text-muted fs-8 ms-8">
                                                Enable this branch to offer the service.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-3">
                                        <span
                                            class="branch-availability-label badge {{ $active ? 'badge-light-success' : 'badge-light-danger' }}">
                                            {{ $active ? 'Available' : 'Unavailable' }}
                                        </span>
                                        <label class="form-check form-switch form-check-custom form-check-solid">
                                            <input class="form-check-input branch-active-checkbox" type="checkbox"
                                                name="branches[{{ $branch->id }}][is_active]" value="1"
                                                @checked($active)>
                                        </label>
                                    </div>
                                </div>

                                <div class="branch-config-fields {{ !$enabled ? 'opacity-50' : '' }}">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold fs-7">
                                                Price Override
                                            </label>
                                            <div class="input-group input-group-solid">
                                                <span class="input-group-text">
                                                    LKR
                                                </span>
                                                <input type="number" name="branches[{{ $branch->id }}][price]"
                                                    value="{{ $price }}" class="form-control" min="0"
                                                    step="0.01" placeholder="Use default"
                                                    @disabled(!$enabled)>
                                            </div>
                                            <div class="text-muted fs-8 mt-2">
                                                Leave empty to use the service default price.
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold fs-7">
                                                Duration Override
                                            </label>
                                            <div class="input-group input-group-solid">
                                                <input type="number"
                                                    name="branches[{{ $branch->id }}][duration_minutes]"
                                                    value="{{ $duration }}" class="form-control" min="5"
                                                    max="1440" placeholder="Use default"
                                                    @disabled(!$enabled)>
                                                <span class="input-group-text">
                                                    min
                                                </span>
                                            </div>
                                            <div class="text-muted fs-8 mt-2">
                                                Leave empty to use the default duration.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div
                                class="d-flex flex-column align-items-center justify-content-center text-center bg-light-warning rounded-3 py-12 px-6">
                                <span class="symbol symbol-70px mb-5">
                                    <span class="symbol-label bg-warning bg-opacity-10 rounded-circle">
                                        <i class="bi bi-shop-window fs-2x text-warning"></i>
                                    </span>
                                </span>
                                <h3 class="fw-bold text-gray-900 mb-2">
                                    No active branches available
                                </h3>
                                <div class="text-muted mw-500px">
                                    Create at least one active branch before assigning
                                    service availability.
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm position-sticky service-settings-card" style="top: 100px;">
            <div class="card-header border-0 pt-8 pb-4">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <span class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-success">
                                <i class="bi bi-sliders fs-2 text-success"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Pricing & Settings
                            </h2>
                            <div class="text-muted fs-8">
                                Configure the default service rules.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-5">
                <div class="mb-7">
                    <label class="form-label required fw-semibold">
                        Default Price
                    </label>
                    <div class="input-group input-group-solid">
                        <span class="input-group-text fw-bold">
                            LKR
                        </span>
                        <input type="number" name="default_price"
                            value="{{ old('default_price', $service->default_price ?? ($service->price ?? 0)) }}"
                            class="form-control fw-semibold @error('default_price') is-invalid @enderror"
                            min="0" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="text-muted fs-8 mt-2">
                        Used whenever a branch does not have its own price.
                    </div>
                    @error('default_price')
                        <div class="text-danger fs-7 mt-2">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-7">
                    <label class="form-label required fw-semibold">
                        Default Duration
                    </label>
                    <div class="input-group input-group-solid">
                        <input type="number" name="default_duration_minutes"
                            value="{{ old('default_duration_minutes', $service->default_duration_minutes ?? ($service->duration_minutes ?? 60)) }}"
                            class="form-control fw-semibold @error('default_duration_minutes') is-invalid @enderror"
                            min="5" max="1440" required>
                        <span class="input-group-text">
                            minutes
                        </span>
                    </div>
                    <div class="text-muted fs-8 mt-2">
                        Used as the base appointment duration.
                    </div>
                    @error('default_duration_minutes')
                        <div class="text-danger fs-7 mt-2">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-7">
                    <label class="form-label fw-semibold">
                        Sort Order
                    </label>
                    <input type="number" name="sort_order"
                        value="{{ old('sort_order', $service->sort_order ?? 0) }}"
                        class="form-control form-control-solid" min="0">
                    <div class="text-muted fs-8 mt-2">
                        Lower values appear earlier in the service catalog.
                    </div>
                </div>

                <div class="separator separator-dashed mb-7"></div>
                <div class="d-flex align-items-center justify-content-between p-5 rounded-3 bg-light">
                    <div>
                        <div class="fw-bold text-gray-900 mb-1">
                            Service Status
                        </div>
                        <div class="text-muted fs-8">
                            Allow this service to be used for future bookings.
                        </div>
                    </div>
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                            @checked(old('is_active', $service->is_active ?? true))>
                    </label>
                </div>

                <div class="separator separator-dashed my-7"></div>
                <div class="rounded-3 bg-light-primary p-5">
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-lightbulb text-primary fs-3 me-3"></i>
                        <span class="fw-bold text-gray-900">
                            How branch pricing works
                        </span>
                    </div>
                    <div class="text-gray-700 fs-8 lh-lg">
                        Branches without an override automatically inherit
                        this service's default price and duration.
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-7">
                <div class="d-grid gap-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi {{ $isEdit ? 'bi-check2-circle' : 'bi-plus-circle' }} me-2"></i>
                        {{ $isEdit ? 'Save Service Changes' : 'Create Service' }}
                    </button>
                    <a href="{{ $isEdit ? route('services.show', $service) : route('services.index') }}"
                        class="btn btn-light">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .service-branch-card {
            background: var(--bs-body-bg);
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease,
                background-color .2s ease;
        }

        .service-branch-card:hover {
            border-color: var(--bs-primary) !important;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .05);
            transform: translateY(-1px);
        }

        .service-branch-card.is-enabled {
            border-color: rgba(var(--bs-primary-rgb), .35) !important;
            background:
                linear-gradient(135deg,
                    rgba(var(--bs-primary-rgb), .035),
                    transparent);
        }

        .service-branch-card:not(.is-enabled) {
            background: var(--bs-gray-100);
        }

        .service-branch-card .branch-config-fields {
            transition:
                opacity .2s ease,
                filter .2s ease;
        }

        .service-settings-card {
            overflow: hidden;
        }

        @media (max-width: 1199.98px) {
            .service-settings-card {
                position: static !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /*
             * Super Admin Tenant Selection
             */
            const tenantSelect = document.getElementById('service_tenant_id');
            tenantSelect?.addEventListener('change', function() {
                if (!this.value) {
                    return;
                }
                const url =
                    new URL(
                        @json(route('services.create')),
                        window.location.origin
                    );
                url.searchParams.set(
                    'tenant_id',
                    this.value
                );
                window.location.href = url.toString();
            });

            /*
             * Branch Configuration Cards
             */
            document
                .querySelectorAll('[data-branch-card]')
                .forEach(function(card) {
                    const enabledCheckbox =
                        card.querySelector(
                            '.branch-enabled-checkbox'
                        );

                    const activeCheckbox =
                        card.querySelector(
                            '.branch-active-checkbox'
                        );

                    const configuration =
                        card.querySelector(
                            '.branch-config-fields'
                        );

                    const fields =
                        configuration?.querySelectorAll(
                            'input[type="number"]'
                        ) ?? [];

                    const availabilityLabel =
                        card.querySelector(
                            '.branch-availability-label'
                        );

                    function refreshEnabledState() {
                        const enabled = enabledCheckbox.checked;
                        card.classList.toggle(
                            'is-enabled',
                            enabled
                        );

                        configuration?.classList.toggle(
                            'opacity-50',
                            !enabled
                        );

                        fields.forEach(function(field) {
                            field.disabled = !enabled;
                        });

                        if (!enabled) {
                            activeCheckbox.disabled = true;
                        } else {
                            activeCheckbox.disabled = false;
                        }
                    }

                    function refreshAvailabilityState() {

                        const active = activeCheckbox.checked;
                        availabilityLabel.classList.toggle(
                            'badge-light-success',
                            active
                        );

                        availabilityLabel.classList.toggle(
                            'badge-light-danger',
                            !active
                        );

                        availabilityLabel.textContent =
                            active ?
                            'Available' :
                            'Unavailable';
                    }

                    enabledCheckbox.addEventListener(
                        'change',
                        refreshEnabledState
                    );

                    activeCheckbox.addEventListener(
                        'change',
                        refreshAvailabilityState
                    );

                    refreshEnabledState();
                    refreshAvailabilityState();
                });
        });
    </script>
@endpush
