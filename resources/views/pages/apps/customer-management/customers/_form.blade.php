@php
    $isEdit = $customer->exists;

    $customerInitials = collect([old('first_name', $customer->first_name), old('last_name', $customer->last_name)])
        ->filter()
        ->map(fn($name) => strtoupper(substr(trim($name), 0, 1)))
        ->take(2)
        ->implode('');

    $customerInitials = $customerInitials ?: 'CU';
@endphp

{{-- ================================================================ --}}
{{-- VALIDATION SUMMARY --}}
{{-- ================================================================ --}}
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">
        <div class="symbol symbol-45px me-4 flex-shrink-0">
            <div class="symbol-label bg-light-danger">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
            </div>
        </div>
        <div>
            <div class="fw-bold text-gray-900 fs-6 mb-1">
                We couldn't save this customer
            </div>
            <div class="text-gray-700">
                {{ $errors->first() }}
            </div>
            @if ($errors->count() > 1)
                <div class="text-muted fs-8 mt-1">
                    Please review the highlighted fields below.
                </div>
            @endif
        </div>
    </div>
@endif


<div class="row g-8">

    {{-- ============================================================ --}}
    {{-- LEFT COLUMN --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">


        {{-- ======================================================== --}}
        {{-- PERSONAL INFORMATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-person-vcard text-primary fs-2"></i>
                        </div>
                    </div>

                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Personal Information
                        </h2>

                        <div class="text-muted fs-8">
                            Basic identity and contact information for this customer.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- Tenant --}}
                    @if (($tenants ?? collect())->isNotEmpty() && !$isEdit)

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
                                            Choose Customer Salon
                                        </div>

                                        <div class="text-muted fs-8">
                                            Branch options depend on the selected salon.
                                        </div>
                                    </div>

                                </div>


                                <label class="form-label required fw-semibold">
                                    Salon / Tenant
                                </label>

                                <select name="tenant_id" id="customer_tenant_id" data-control="select2"
                                    data-hide-search="true"
                                    class="form-select bg-white @error('tenant_id') is-invalid @enderror" required>
                                    <option value="">
                                        Select salon
                                    </option>

                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>
                                            {{ $tenant->name }}
                                            {{ $tenant->email ? ' - ' . $tenant->email : '' }}
                                        </option>
                                    @endforeach

                                </select>

                                @error('tenant_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    @endif


                    {{-- Customer Code --}}
                    @if ($isEdit)
                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Customer Code
                            </label>

                            <div class="input-group">

                                <span class="input-group-text border-0 bg-light">
                                    <i class="bi bi-upc-scan text-muted"></i>
                                </span>

                                <input type="text" value="{{ $customer->customer_code }}"
                                    class="form-control bg-light" readonly>

                            </div>

                            <div class="text-muted fs-9 mt-2">
                                Automatically generated identifier.
                            </div>

                        </div>
                    @endif


                    {{-- First Name --}}
                    <div class="col-md-{{ $isEdit ? 4 : 6 }}">

                        <label class="form-label required fw-semibold">
                            First Name
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-person text-muted"></i>
                            </span>

                            <input type="text" name="first_name"
                                value="{{ old('first_name', $customer->first_name) }}"
                                class="form-control @error('first_name') is-invalid @enderror" placeholder="e.g. Amelia"
                                autocomplete="given-name" required>

                            @error('first_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Last Name --}}
                    <div class="col-md-{{ $isEdit ? 4 : 6 }}">

                        <label class="form-label fw-semibold">
                            Last Name
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-person text-muted"></i>
                            </span>

                            <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}"
                                class="form-control @error('last_name') is-invalid @enderror"
                                placeholder="e.g. Williams" autocomplete="family-name">

                            @error('last_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Phone --}}
                    <div class="col-md-6">

                        <label class="form-label required fw-semibold">
                            Phone Number
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-telephone text-muted"></i>
                            </span>

                            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                                class="form-control @error('phone') is-invalid @enderror" placeholder="+94 77 123 4567"
                                autocomplete="tel" required>

                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Email --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Email Address
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>

                            <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="customer@example.com" autocomplete="email">

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Gender --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Gender
                        </label>

                        <select name="gender" class="form-select @error('gender') is-invalid @enderror"
                            data-control="select2" data-hide-search="true">
                            <option value="">
                                Not specified
                            </option>

                            @foreach ($genders as $gender)
                                <option value="{{ $gender }}" @selected(old('gender', $customer->gender) === $gender)>
                                    {{ str($gender)->headline() }}
                                </option>
                            @endforeach

                        </select>

                        @error('gender')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- DOB --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Date of Birth
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-calendar3 text-muted"></i>
                            </span>

                            <input type="date" name="date_of_birth"
                                value="{{ old('date_of_birth', optional($customer->date_of_birth)->format('Y-m-d')) }}"
                                class="form-control @error('date_of_birth') is-invalid @enderror">

                            @error('date_of_birth')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- CONTACT & PREFERENCES --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-chat-heart text-info fs-2"></i>
                        </div>
                    </div>

                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Contact & Preferences
                        </h2>

                        <div class="text-muted fs-8">
                            Save customer preferences and useful salon notes.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- Address --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Address
                        </label>

                        <textarea name="address" rows="3"
                            class="form-control form-control-solid @error('address') is-invalid @enderror"
                            placeholder="Enter customer's residential or preferred contact address...">{{ old('address', $customer->address) }}</textarea>

                        @error('address')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Profile Notes --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Customer Profile Notes
                        </label>

                        <textarea name="notes" rows="4" class="form-control form-control-solid @error('notes') is-invalid @enderror"
                            placeholder="Long-term preferences, allergies, style preferences or important customer details...">{{ old('notes', $customer->notes) }}</textarea>

                        @error('notes')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror


                        <div class="rounded-3 bg-light-warning p-4 mt-4">

                            <div class="d-flex align-items-start">

                                <div class="symbol symbol-35px me-3 flex-shrink-0">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-lightbulb text-warning"></i>
                                    </div>
                                </div>

                                <div>

                                    <div class="fw-semibold text-gray-900 fs-8 mb-1">
                                        What should go here?
                                    </div>

                                    <div class="text-muted fs-8">
                                        Use profile notes for information that should remain visible
                                        across future appointments.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Timeline Note --}}
                    <div class="col-12">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <label class="form-label fw-semibold mb-0">
                                Add Note to Customer History
                            </label>

                            <span class="badge badge-light-primary">
                                Timeline Note
                            </span>

                        </div>

                        <textarea name="note" rows="3" class="form-control form-control-solid @error('note') is-invalid @enderror"
                            placeholder="Add today's observation or preference...">{{ old('note') }}</textarea>

                        @error('note')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror


                        <div class="rounded-3 bg-light-primary p-4 mt-4">

                            <div class="text-muted fs-8">
                                <i class="bi bi-stars text-primary me-2"></i>

                                Example:
                                Sensitive scalp, prefers senior stylist,
                                Saturday mornings and low-fragrance products.
                            </div>

                        </div>

                    </div>


                    {{-- Marketing Consent --}}
                    <div class="col-12">

                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-megaphone text-success fs-3"></i>
                                    </div>
                                </div>


                                <div>

                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">

                                        <span class="fw-bold text-gray-900">
                                            Marketing Consent
                                        </span>

                                        <span class="badge badge-light">
                                            Optional
                                        </span>

                                    </div>

                                    <div class="text-muted fs-8">
                                        Allow promotions, offers and marketing communications.
                                    </div>

                                </div>

                            </div>


                            <div class="form-check form-switch form-check-custom form-check-solid ms-4">

                                <input class="form-check-input" type="checkbox" name="marketing_consent"
                                    value="1" @checked(old('marketing_consent', $customer->marketing_consent ?? false))>

                            </div>

                        </label>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- RIGHT SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">


        {{-- ======================================================== --}}
        {{-- CUSTOMER PREVIEW --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-body text-center p-7">

                <div class="symbol symbol-80px mb-5">

                    <div class="symbol-label bg-light-primary text-primary fs-2 fw-bolder rounded-4">
                        {{ $customerInitials }}
                    </div>

                </div>


                <h3 class="fw-bold text-gray-900 mb-1" id="customer_preview_name">
                    {{ trim(old('first_name', $customer->first_name) . ' ' . old('last_name', $customer->last_name)) ?:
                        'New Customer' }}
                </h3>


                @if ($isEdit)
                    <div class="text-muted fs-8 mb-4">
                        {{ $customer->customer_code }}
                    </div>
                @else
                    <div class="text-muted fs-8 mb-4">
                        Customer profile preview
                    </div>
                @endif


                <span class="badge badge-light-success px-3 py-2" id="customer_preview_status">
                    <i class="bi bi-circle-fill fs-10 me-2"></i>
                    {{ str(old('status', $customer->status ?? 'active'))->headline() }}
                </span>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- SALON INFORMATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm position-sticky top-100px">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-shop-window text-primary fs-2"></i>
                        </div>
                    </div>

                    <div>

                        <h2 class="fw-bold text-gray-900 mb-1">
                            Salon Information
                        </h2>

                        <div class="text-muted fs-8">
                            Branch assignment and customer status.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- Branch --}}
                <div class="mb-7">

                    <label class="form-label fw-semibold">
                        Primary Branch
                    </label>

                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror"
                        data-control="select2" data-hide-search="true">
                        <option value="">
                            No primary branch
                        </option>

                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $customer->branch_id) === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach

                    </select>

                    @error('branch_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="text-muted fs-8 mt-2">
                        Preferred or default salon branch for this customer.
                    </div>

                </div>


                {{-- Status --}}
                <div class="mb-7">

                    <label class="form-label required fw-semibold">
                        Customer Status
                    </label>

                    <select name="status" id="customer_status" data-control="select2" data-hide-search="true"
                        class="form-select @error('status') is-invalid @enderror" required>

                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $customer->status ?? 'active') === $status)>
                                {{ str($status)->headline() }}
                            </option>
                        @endforeach

                    </select>

                    @error('status')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Profile Summary --}}
                <div class="rounded-4 bg-light p-5">

                    <div class="fw-bold text-gray-900 mb-5">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Profile Summary
                    </div>


                    <div
                        class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom border-gray-300">

                        <span class="text-muted fs-8">
                            Mode
                        </span>

                        <span class="badge badge-light-primary">
                            {{ $isEdit ? 'Editing' : 'Creating' }}
                        </span>

                    </div>


                    <div
                        class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom border-gray-300">

                        <span class="text-muted fs-8">
                            Branches Available
                        </span>

                        <span class="fw-bold text-gray-900">
                            {{ $branches->count() }}
                        </span>

                    </div>


                    <div class="d-flex justify-content-between align-items-center">

                        <span class="text-muted fs-8">
                            Marketing
                        </span>

                        <span class="badge badge-light">
                            Optional
                        </span>

                    </div>

                </div>

            </div>


            {{-- Actions --}}
            <div class="card-footer border-0 pt-2">

                <div class="d-grid gap-3">

                    <button type="submit" class="btn btn-primary btn-lg">

                        @if ($isEdit)
                            <i class="bi bi-check2-circle me-2"></i>
                            Save Customer Changes
                        @else
                            <i class="bi bi-person-plus me-2"></i>
                            Create Customer
                        @endif

                    </button>


                    <a href="{{ $isEdit
                        ? route('customer-management.customers.show', $customer)
                        : route('customer-management.customers.index') }}"
                        class="btn btn-light">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancel
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /*
            |--------------------------------------------------------------------------
            | Tenant Selector
            |--------------------------------------------------------------------------
            */

            const tenantSelect =
                document.getElementById('customer_tenant_id');

            tenantSelect?.addEventListener('change', function() {

                if (!this.value) {
                    return;
                }

                const createUrl =
                    @json(route('customer-management.customers.create'));

                const url =
                    new URL(createUrl, window.location.origin);

                url.searchParams.set(
                    'tenant_id',
                    this.value
                );

                window.location.href =
                    url.toString();

            });


            /*
            |--------------------------------------------------------------------------
            | Live Customer Preview
            |--------------------------------------------------------------------------
            */

            const firstNameInput =
                document.querySelector('[name="first_name"]');

            const lastNameInput =
                document.querySelector('[name="last_name"]');

            const previewName =
                document.getElementById('customer_preview_name');


            function updateCustomerPreview() {

                if (!previewName) {
                    return;
                }

                const firstName =
                    firstNameInput?.value?.trim() ?? '';

                const lastName =
                    lastNameInput?.value?.trim() ?? '';

                const fullName =
                    `${firstName} ${lastName}`.trim();

                previewName.textContent =
                    fullName || 'New Customer';

            }


            firstNameInput?.addEventListener(
                'input',
                updateCustomerPreview
            );

            lastNameInput?.addEventListener(
                'input',
                updateCustomerPreview
            );


            /*
            |--------------------------------------------------------------------------
            | Live Status Preview
            |--------------------------------------------------------------------------
            */

            const statusSelect =
                document.getElementById('customer_status');

            const statusPreview =
                document.getElementById('customer_preview_status');


            statusSelect?.addEventListener('change', function() {

                if (!statusPreview) {
                    return;
                }

                const statusText =
                    this.options[this.selectedIndex]
                    ?.text
                    ?.trim() ??
                    this.value;

                statusPreview.innerHTML =
                    `<i class="bi bi-circle-fill fs-10 me-2"></i>${statusText}`;

            });

        });
    </script>
@endpush
