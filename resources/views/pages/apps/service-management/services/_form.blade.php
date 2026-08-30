@php
    $isEdit = $service->exists;
@endphp


{{-- ================================================================ --}}
{{-- VALIDATION ALERT --}}
{{-- ================================================================ --}}
@if ($errors->any())

    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">

        <div class="symbol symbol-45px me-4 flex-shrink-0">
            <div class="symbol-label bg-light-danger">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
            </div>
        </div>

        <div>
            <div class="fw-bold fs-6 text-gray-900 mb-1">
                Please check the service details
            </div>

            <div class="text-gray-700">
                {{ $errors->first() }}
            </div>

            @if ($errors->count() > 1)
                <div class="text-muted fs-8 mt-1">
                    Review the highlighted fields before saving.
                </div>
            @endif
        </div>

    </div>

@endif


<div class="row g-8">

    {{-- ============================================================ --}}
    {{-- LEFT CONTENT --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">


        {{-- ======================================================== --}}
        {{-- SERVICE INFORMATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-scissors text-primary fs-2"></i>
                        </div>
                    </div>

                    <div>

                        <h2 class="fw-bold text-gray-900 mb-1">
                            Service Information
                        </h2>

                        <div class="text-muted fs-8">
                            Define the main service identity and customer-facing information.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- ================================================= --}}
                    {{-- TENANT --}}
                    {{-- ================================================= --}}
                    @if (($tenants ?? collect())->isNotEmpty())

                        <div class="col-12">

                            <div class="rounded-4 bg-light-primary p-5">

                                <div class="d-flex align-items-center mb-5">

                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-white">
                                            <i class="bi bi-shop text-primary fs-3"></i>
                                        </div>
                                    </div>

                                    <div>

                                        <div class="fw-bold text-gray-900 mb-1">
                                            Service Owner
                                        </div>

                                        <div class="text-muted fs-8">
                                            Select the salon that owns this service.
                                        </div>

                                    </div>

                                </div>


                                <label class="form-label required fw-semibold">
                                    Salon / Tenant
                                </label>


                                <select name="tenant_id" id="service_tenant_id"
                                    class="form-select bg-white @error('tenant_id') is-invalid @enderror"
                                    data-control="select2" data-hide-search="true" required>

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


                                @error('tenant_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror


                                <div class="text-muted fs-8 mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Categories and branches will load for the selected salon.
                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- ================================================= --}}
                    {{-- SERVICE NAME --}}
                    {{-- ================================================= --}}
                    <div class="col-lg-8">

                        <label class="form-label required fw-semibold">
                            Service Name
                        </label>


                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-stars text-muted"></i>
                            </span>


                            <input type="text" name="name" value="{{ old('name', $service->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="e.g. Signature Haircut" required>


                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="text-muted fs-8 mt-2">
                            Use a short and recognizable service name.
                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- CATEGORY --}}
                    {{-- ================================================= --}}
                    <div class="col-lg-4">

                        <label class="form-label fw-semibold">
                            Category
                        </label>


                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror"
                            data-control="select2" data-hide-search="true">

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
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror


                        <div class="text-muted fs-8 mt-2">
                            Helps organize the service catalog.
                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- DESCRIPTION --}}
                    {{-- ================================================= --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Description
                        </label>


                        <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror"
                            rows="5" placeholder="Briefly describe what this service includes...">{{ old('description', $service->description) }}</textarea>


                        @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror


                        <div class="rounded-3 bg-light p-4 mt-4">

                            <div class="d-flex align-items-start">

                                <div class="symbol symbol-35px me-3 flex-shrink-0">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-lightbulb text-warning"></i>
                                    </div>
                                </div>

                                <div>

                                    <div class="fw-semibold text-gray-900 fs-8 mb-1">
                                        Description Tip
                                    </div>

                                    <div class="text-muted fs-8">
                                        Keep it useful for staff, POS and future online booking pages.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- BRANCH AVAILABILITY --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-buildings text-info fs-2"></i>
                        </div>
                    </div>

                    <div>

                        <h2 class="fw-bold text-gray-900 mb-1">
                            Branch Availability
                        </h2>

                        <div class="text-muted fs-8">
                            Choose where this service is offered and define branch-level overrides.
                        </div>

                    </div>

                </div>


                @if ($branches->isNotEmpty())
                    <div class="card-toolbar">

                        <span class="badge badge-light-primary px-3 py-2">
                            {{ number_format($branches->count()) }}
                            {{ Str::plural('Branch', $branches->count()) }}
                        </span>

                    </div>
                @endif

            </div>


            <div class="card-body pt-4">

                @if ($branches->isNotEmpty())

                    <div class="d-flex flex-column gap-5">

                        @foreach ($branches as $branch)
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


                            <div class="rounded-4 border {{ $enabled ? 'border-primary bg-light-primary' : 'border-gray-300' }} p-5"
                                data-branch-card>

                                <input type="hidden" name="branches[{{ $branch->id }}][enabled]" value="0">

                                <input type="hidden" name="branches[{{ $branch->id }}][is_active]" value="0">


                                {{-- ================================================= --}}
                                {{-- BRANCH HEADER --}}
                                {{-- ================================================= --}}
                                <div
                                    class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-50px me-4 flex-shrink-0">

                                            <div class="symbol-label {{ $enabled ? 'bg-white' : 'bg-light-primary' }}">

                                                <i class="bi bi-building text-primary fs-3"></i>

                                            </div>

                                        </div>


                                        <div>

                                            <label class="form-check form-check-custom form-check-solid mb-2">

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
                                            class="branch-availability-label badge {{ $active ? 'badge-light-success' : 'badge-light-danger' }} px-3 py-2">
                                            <i
                                                class="bi {{ $active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>

                                            {{ $active ? 'Available' : 'Unavailable' }}
                                        </span>


                                        <div class="form-check form-switch form-check-custom form-check-solid">

                                            <input class="form-check-input branch-active-checkbox" type="checkbox"
                                                name="branches[{{ $branch->id }}][is_active]" value="1"
                                                @checked($active)>

                                        </div>

                                    </div>

                                </div>


                                <div class="separator separator-dashed my-5"></div>


                                {{-- ================================================= --}}
                                {{-- CONFIGURATION --}}
                                {{-- ================================================= --}}
                                <div class="branch-config-fields {{ !$enabled ? 'opacity-50' : '' }}">

                                    <div class="row g-5">

                                        {{-- Price Override --}}
                                        <div class="col-md-6">

                                            <div class="rounded-3 bg-light p-4 h-100">

                                                <div class="d-flex align-items-center mb-4">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-white">
                                                            <i class="bi bi-cash-stack text-success"></i>
                                                        </div>
                                                    </div>

                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            Price Override
                                                        </div>

                                                        <div class="text-muted fs-9">
                                                            Optional branch-specific price
                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="input-group">

                                                    <span class="input-group-text border-0 bg-white fw-semibold">
                                                        LKR
                                                    </span>

                                                    <input type="number" name="branches[{{ $branch->id }}][price]"
                                                        value="{{ $price }}" class="form-control bg-white"
                                                        min="0" step="0.01" placeholder="Use default"
                                                        @disabled(!$enabled)>

                                                </div>


                                                <div class="text-muted fs-8 mt-2">
                                                    Leave empty to inherit the default price.
                                                </div>

                                            </div>

                                        </div>


                                        {{-- Duration Override --}}
                                        <div class="col-md-6">

                                            <div class="rounded-3 bg-light p-4 h-100">

                                                <div class="d-flex align-items-center mb-4">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-white">
                                                            <i class="bi bi-clock-history text-info"></i>
                                                        </div>
                                                    </div>

                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            Duration Override
                                                        </div>

                                                        <div class="text-muted fs-9">
                                                            Optional branch-specific duration
                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="input-group">

                                                    <input type="number"
                                                        name="branches[{{ $branch->id }}][duration_minutes]"
                                                        value="{{ $duration }}" class="form-control bg-white"
                                                        min="5" max="1440" placeholder="Use default"
                                                        @disabled(!$enabled)>

                                                    <span class="input-group-text border-0 bg-white">
                                                        min
                                                    </span>

                                                </div>


                                                <div class="text-muted fs-8 mt-2">
                                                    Leave empty to inherit the default duration.
                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                {{-- ================================================= --}}
                                {{-- STATE SUMMARY --}}
                                {{-- ================================================= --}}
                                <div class="mt-5">

                                    @if ($enabled)
                                        <div class="d-flex align-items-center">

                                            <div class="symbol symbol-35px me-3">
                                                <div class="symbol-label bg-light-success">
                                                    <i class="bi bi-check2 text-success"></i>
                                                </div>
                                            </div>

                                            <div>

                                                <div class="fw-semibold text-gray-900 fs-8">
                                                    Branch Enabled
                                                </div>

                                                <div class="text-muted fs-9">
                                                    This service can be configured for {{ $branch->name }}.
                                                </div>

                                            </div>

                                        </div>
                                    @else
                                        <div class="d-flex align-items-center">

                                            <div class="symbol symbol-35px me-3">
                                                <div class="symbol-label bg-light">
                                                    <i class="bi bi-slash-circle text-muted"></i>
                                                </div>
                                            </div>

                                            <div>

                                                <div class="fw-semibold text-gray-700 fs-8">
                                                    Not Assigned
                                                </div>

                                                <div class="text-muted fs-9">
                                                    Enable the branch to configure availability and pricing.
                                                </div>

                                            </div>

                                        </div>
                                    @endif

                                </div>

                            </div>
                        @endforeach

                    </div>
                @else
                    {{-- ================================================= --}}
                    {{-- EMPTY STATE --}}
                    {{-- ================================================= --}}
                    <div class="text-center py-15">

                        <div class="symbol symbol-90px mb-6">
                            <div class="symbol-label bg-light-warning rounded-circle">
                                <i class="bi bi-shop-window fs-1 text-warning"></i>
                            </div>
                        </div>


                        <h3 class="fw-bold text-gray-900 mb-2">
                            No active branches available
                        </h3>


                        <div class="text-muted fs-6 mx-auto">
                            Create at least one active branch before assigning
                            service availability.
                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- RIGHT SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">

        {{-- ======================================================== --}}
        {{-- SERVICE PREVIEW --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-body text-center p-7">

                <div class="symbol symbol-75px mb-5">

                    <div class="symbol-label bg-light-primary rounded-4">
                        <i class="bi bi-scissors text-primary fs-1"></i>
                    </div>

                </div>


                <h3 id="service_preview_name" class="fw-bold text-gray-900 mb-2">
                    {{ old('name', $service->name) ?: 'New Service' }}
                </h3>


                <div class="text-muted fs-8 mb-4">
                    {{ $isEdit ? 'Service configuration preview' : 'New service preview' }}
                </div>


                <span id="service_preview_status"
                    class="badge {{ old('is_active', $service->is_active ?? true) ? 'badge-light-success' : 'badge-light-danger' }} px-3 py-2">
                    <i class="bi bi-circle-fill fs-9 me-2"></i>

                    {{ old('is_active', $service->is_active ?? true) ? 'Active' : 'Inactive' }}
                </span>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- PRICING & SETTINGS --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-sliders text-success fs-3"></i>
                        </div>
                    </div>

                    <div>

                        <h2 class="fw-bold text-gray-900 mb-1">
                            Pricing & Settings
                        </h2>

                        <div class="text-muted fs-8">
                            Configure default service rules.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- ================================================= --}}
                {{-- DEFAULT PRICE --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label required fw-semibold">
                        Default Price
                    </label>


                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light fw-bold">
                            LKR
                        </span>


                        <input type="number" name="default_price"
                            value="{{ old('default_price', $service->default_price ?? ($service->price ?? 0)) }}"
                            class="form-control fw-semibold @error('default_price') is-invalid @enderror"
                            min="0" step="0.01" placeholder="0.00" required>


                        @error('default_price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="text-muted fs-8 mt-2">
                        Used whenever a branch does not define its own price.
                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- DEFAULT DURATION --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label required fw-semibold">
                        Default Duration
                    </label>


                    <div class="input-group">

                        <input type="number" name="default_duration_minutes"
                            value="{{ old('default_duration_minutes', $service->default_duration_minutes ?? ($service->duration_minutes ?? 60)) }}"
                            class="form-control fw-semibold @error('default_duration_minutes') is-invalid @enderror"
                            min="5" max="1440" required>

                        <span class="input-group-text border-0 bg-light">
                            minutes
                        </span>


                        @error('default_duration_minutes')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="text-muted fs-8 mt-2">
                        Base appointment duration used by branches.
                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- SORT ORDER --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label fw-semibold">
                        Sort Order
                    </label>


                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-sort-numeric-down text-muted"></i>
                        </span>


                        <input type="number" name="sort_order"
                            value="{{ old('sort_order', $service->sort_order ?? 0) }}"
                            class="form-control @error('sort_order') is-invalid @enderror" min="0">


                        @error('sort_order')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="text-muted fs-8 mt-2">
                        Lower values appear earlier in the catalog.
                    </div>

                </div>


                <div class="separator separator-dashed my-7"></div>


                {{-- ================================================= --}}
                {{-- STATUS --}}
                {{-- ================================================= --}}
                <label
                    class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-40px me-4">

                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-power text-success"></i>
                            </div>

                        </div>


                        <div>

                            <div class="fw-bold text-gray-900 mb-1">
                                Service Status
                            </div>

                            <div class="text-muted fs-8">
                                Allow this service to be used for bookings.
                            </div>

                        </div>

                    </div>


                    <div class="form-check form-switch form-check-custom form-check-solid ms-3">

                        <input id="service_status_toggle" class="form-check-input" type="checkbox" name="is_active"
                            value="1" @checked(old('is_active', $service->is_active ?? true))>

                    </div>

                </label>


                <div class="separator separator-dashed my-7"></div>


                {{-- ================================================= --}}
                {{-- INFORMATION --}}
                {{-- ================================================= --}}
                <div class="rounded-4 bg-light-primary p-5">

                    <div class="d-flex align-items-center mb-4">

                        <div class="symbol symbol-35px me-3">
                            <div class="symbol-label bg-white">
                                <i class="bi bi-lightbulb text-primary"></i>
                            </div>
                        </div>

                        <span class="fw-bold text-gray-900">
                            How branch pricing works
                        </span>

                    </div>


                    <div class="text-gray-700 fs-8 lh-lg">
                        Branches without overrides automatically inherit
                        the default price and duration configured above.
                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- FORM ACTIONS --}}
            {{-- ===================================================== --}}
            <div class="card-footer border-0 pt-2 pb-7">

                <div class="d-grid gap-3">

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi {{ $isEdit ? 'bi-check2-circle' : 'bi-plus-circle' }} me-2"></i>

                        {{ $isEdit ? 'Save Service Changes' : 'Create Service' }}
                    </button>


                    <a href="{{ $isEdit ? route('services.show', $service) : route('services.index') }}"
                        class="btn btn-light">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancel
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ================================================================ --}}
{{-- SCRIPTS --}}
{{-- ================================================================ --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /*
            |--------------------------------------------------------------------------
            | Super Admin Tenant Selection
            |--------------------------------------------------------------------------
            */

            const tenantSelect =
                document.getElementById('service_tenant_id');

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

                window.location.href =
                    url.toString();

            });


            /*
            |--------------------------------------------------------------------------
            | Live Service Preview
            |--------------------------------------------------------------------------
            */

            const serviceNameInput =
                document.querySelector('[name="name"]');

            const servicePreviewName =
                document.getElementById('service_preview_name');

            serviceNameInput?.addEventListener('input', function() {

                if (!servicePreviewName) {
                    return;
                }

                servicePreviewName.textContent =
                    this.value.trim() || 'New Service';

            });


            /*
            |--------------------------------------------------------------------------
            | Live Service Status
            |--------------------------------------------------------------------------
            */

            const statusToggle =
                document.getElementById('service_status_toggle');

            const statusPreview =
                document.getElementById('service_preview_status');


            function refreshServiceStatus() {

                if (!statusToggle || !statusPreview) {
                    return;
                }

                const active =
                    statusToggle.checked;

                statusPreview.classList.toggle(
                    'badge-light-success',
                    active
                );

                statusPreview.classList.toggle(
                    'badge-light-danger',
                    !active
                );

                statusPreview.innerHTML =
                    `
                        <i class="bi bi-circle-fill fs-9 me-2"></i>
                        ${active ? 'Active' : 'Inactive'}
                    `;

            }


            statusToggle?.addEventListener(
                'change',
                refreshServiceStatus
            );


            /*
            |--------------------------------------------------------------------------
            | Branch Configuration
            |--------------------------------------------------------------------------
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

                        const enabled =
                            enabledCheckbox.checked;

                        card.classList.toggle(
                            'border-primary',
                            enabled
                        );

                        card.classList.toggle(
                            'bg-light-primary',
                            enabled
                        );

                        card.classList.toggle(
                            'border-gray-300',
                            !enabled
                        );

                        configuration?.classList.toggle(
                            'opacity-50',
                            !enabled
                        );


                        fields.forEach(function(field) {
                            field.disabled = !enabled;
                        });


                        if (activeCheckbox) {
                            activeCheckbox.disabled = !enabled;
                        }

                    }


                    function refreshAvailabilityState() {

                        if (
                            !activeCheckbox ||
                            !availabilityLabel
                        ) {
                            return;
                        }

                        const active =
                            activeCheckbox.checked;


                        availabilityLabel.classList.toggle(
                            'badge-light-success',
                            active
                        );

                        availabilityLabel.classList.toggle(
                            'badge-light-danger',
                            !active
                        );


                        availabilityLabel.innerHTML =
                            `
                                <i class="bi ${
                                    active
                                        ? 'bi-check-circle-fill'
                                        : 'bi-x-circle-fill'
                                } me-1"></i>

                                ${
                                    active
                                        ? 'Available'
                                        : 'Unavailable'
                                }
                            `;

                    }


                    enabledCheckbox?.addEventListener(
                        'change',
                        refreshEnabledState
                    );

                    activeCheckbox?.addEventListener(
                        'change',
                        refreshAvailabilityState
                    );


                    refreshEnabledState();
                    refreshAvailabilityState();

                });


            refreshServiceStatus();

        });
    </script>
@endpush
