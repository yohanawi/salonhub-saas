@php
    $isEdit = $customer->exists;

    $customerInitials = collect([old('first_name', $customer->first_name), old('last_name', $customer->last_name)])
        ->filter()
        ->map(fn($name) => strtoupper(substr(trim($name), 0, 1)))
        ->take(2)
        ->implode('');

    $customerInitials = $customerInitials ?: 'CU';
@endphp

<style>
    .customer-form-card {
        border: 1px solid #f1f1f4 !important;
        transition: all .2s ease;
    }

    .customer-form-card:hover {
        box-shadow: 0 10px 35px rgba(30, 33, 41, .06) !important;
    }

    .customer-form-card .card-header {
        min-height: 75px;
    }

    .customer-section-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
    }

    .customer-preview {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top right,
                rgba(114, 57, 234, .15),
                transparent 35%),
            linear-gradient(145deg,
                #ffffff 0%,
                #faf8ff 100%);
    }

    .customer-preview::after {
        content: '';
        position: absolute;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(114, 57, 234, .05);
        bottom: -100px;
        right: -80px;
    }

    .customer-avatar {
        width: 76px;
        height: 76px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        background: linear-gradient(135deg, #7239ea, #9d6df2);
        color: #fff;
        font-size: 25px;
        font-weight: 700;
        letter-spacing: 1px;
        box-shadow: 0 12px 28px rgba(114, 57, 234, .24);
    }

    .form-field-box {
        position: relative;
    }

    .form-field-icon {
        position: absolute;
        left: 14px;
        top: 42px;
        z-index: 2;
        color: #99a1b7;
        font-size: 16px;
        pointer-events: none;
    }

    .form-field-box .with-icon {
        padding-left: 42px;
    }

    .customer-help-box {
        border: 1px dashed #e4e6ef;
        background: #fcfcfd;
        border-radius: 12px;
    }

    .customer-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 13px 0;
        border-bottom: 1px dashed #e9e9ed;
    }

    .customer-info-row:last-child {
        border-bottom: none;
    }

    .consent-card {
        border: 1px solid #e9e9ed;
        border-radius: 14px;
        background: #fcfcfd;
        transition: all .2s ease;
    }

    .consent-card:hover {
        background: #faf8ff;
        border-color: rgba(114, 57, 234, .25);
    }

    .sticky-customer-panel {
        top: 100px;
    }

    .customer-save-bar {
        background: #fbfbfc;
        border-top: 1px solid #f1f1f4;
    }

    .required-dot {
        width: 6px;
        height: 6px;
        display: inline-block;
        border-radius: 50%;
        background: #f1416c;
        vertical-align: middle;
        margin-left: 4px;
    }

    .note-example {
        border-left: 3px solid #7239ea;
        background: #f8f5ff;
    }

    @media (max-width: 1199.98px) {
        .sticky-customer-panel {
            position: static !important;
        }
    }
</style>


{{-- ================================================================ --}}
{{-- VALIDATION SUMMARY --}}
{{-- ================================================================ --}}
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-7">

        <span class="customer-section-icon bg-light-danger me-4">
            <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
        </span>

        <div>
            <div class="fw-bold fs-6 mb-1">
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


<div class="row g-7">

    {{-- ============================================================ --}}
    {{-- LEFT COLUMN --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">


        {{-- ======================================================== --}}
        {{-- CUSTOMER PROFILE / PERSONAL INFORMATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm customer-form-card mb-7">

            <div class="card-header border-0">

                <div class="card-title">

                    <span class="customer-section-icon bg-light-primary me-4">
                        <i class="bi bi-person-vcard fs-2 text-primary"></i>
                    </span>

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


            <div class="card-body pt-3">

                <div class="row g-6">

                    {{-- Tenant / Salon --}}
                    @if (($tenants ?? collect())->isNotEmpty() && !$isEdit)

                        <div class="col-12">

                            <div class="rounded bg-light-primary p-5">

                                <div class="d-flex align-items-center mb-4">

                                    <span class="symbol symbol-40px me-3">
                                        <span class="symbol-label bg-white">
                                            <i class="bi bi-shop text-primary fs-4"></i>
                                        </span>
                                    </span>

                                    <div>
                                        <div class="fw-bold text-gray-900">
                                            Choose Customer Salon
                                        </div>

                                        <div class="text-muted fs-8">
                                            Branch availability depends on the selected salon.
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
                                    <div class="text-danger fs-7 mt-2">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    @endif


                    {{-- Customer Code --}}
                    @if ($isEdit)
                        <div class="col-md-4">

                            <div class="form-field-box">

                                <label class="form-label fw-semibold">
                                    Customer Code
                                </label>

                                <i class="bi bi-upc-scan form-field-icon"></i>

                                <input type="text" value="{{ $customer->customer_code }}"
                                    class="form-control with-icon bg-light" readonly>

                                <div class="text-muted fs-9 mt-2">
                                    Automatically generated identifier.
                                </div>

                            </div>

                        </div>
                    @endif


                    {{-- First Name --}}
                    <div class="col-md-{{ $isEdit ? 4 : 6 }}">

                        <div class="form-field-box">

                            <label class="form-label required fw-semibold">
                                First Name
                            </label>

                            <i class="bi bi-person form-field-icon"></i>

                            <input type="text" name="first_name"
                                value="{{ old('first_name', $customer->first_name) }}"
                                class="form-control with-icon @error('first_name') is-invalid @enderror"
                                placeholder="e.g. Amelia" autocomplete="given-name" required>

                            @error('first_name')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Last Name --}}
                    <div class="col-md-{{ $isEdit ? 4 : 6 }}">

                        <div class="form-field-box">

                            <label class="form-label fw-semibold">
                                Last Name
                            </label>

                            <i class="bi bi-person form-field-icon"></i>

                            <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}"
                                class="form-control with-icon @error('last_name') is-invalid @enderror"
                                placeholder="e.g. Williams" autocomplete="family-name">

                            @error('last_name')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Phone --}}
                    <div class="col-md-6">

                        <div class="form-field-box">

                            <label class="form-label required fw-semibold">
                                Phone Number
                            </label>

                            <i class="bi bi-telephone form-field-icon"></i>

                            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                                class="form-control with-icon @error('phone') is-invalid @enderror"
                                placeholder="+94 77 123 4567" autocomplete="tel" required>

                            @error('phone')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Email --}}
                    <div class="col-md-6">

                        <div class="form-field-box">

                            <label class="form-label fw-semibold">
                                Email Address
                            </label>

                            <i class="bi bi-envelope form-field-icon"></i>

                            <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                                class="form-control with-icon @error('email') is-invalid @enderror"
                                placeholder="customer@example.com" autocomplete="email">

                            @error('email')
                                <div class="text-danger fs-7 mt-2">
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
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- DOB --}}
                    <div class="col-md-6">

                        <div class="form-field-box">

                            <label class="form-label fw-semibold">
                                Date of Birth
                            </label>

                            <i class="bi bi-calendar3 form-field-icon"></i>

                            <input type="date" name="date_of_birth"
                                value="{{ old('date_of_birth', optional($customer->date_of_birth)->format('Y-m-d')) }}"
                                class="form-control with-icon @error('date_of_birth') is-invalid @enderror">

                            @error('date_of_birth')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- CONTACT / PREFERENCES --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm customer-form-card mb-7">

            <div class="card-header border-0">

                <div class="card-title">

                    <span class="customer-section-icon bg-light-info me-4">
                        <i class="bi bi-chat-heart fs-2 text-info"></i>
                    </span>

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


            <div class="card-body pt-3">

                <div class="row g-6">

                    {{-- Address --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Address
                        </label>

                        <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror"
                            placeholder="Enter customer's residential or preferred contact address...">{{ old('address', $customer->address) }}</textarea>

                        @error('address')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Profile Notes --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Customer Profile Notes
                        </label>

                        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                            placeholder="Long-term preferences, allergies, style preferences or important customer details...">{{ old('notes', $customer->notes) }}</textarea>

                        <div class="customer-help-box p-4 mt-3">

                            <div class="d-flex">

                                <i class="bi bi-lightbulb text-warning fs-4 me-3"></i>

                                <div>
                                    <div class="fw-semibold text-gray-800 fs-8">
                                        What should go here?
                                    </div>

                                    <div class="text-muted fs-8">
                                        Use profile notes for information that should remain visible
                                        across future appointments.
                                    </div>
                                </div>

                            </div>

                        </div>

                        @error('notes')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Note History --}}
                    <div class="col-12">

                        <div class="d-flex justify-content-between align-items-center mb-2">

                            <label class="form-label fw-semibold mb-0">
                                Add Note to Customer History
                            </label>

                            <span class="badge badge-light-primary">
                                Timeline Note
                            </span>

                        </div>

                        <textarea name="note" rows="3" class="form-control @error('note') is-invalid @enderror"
                            placeholder="Add today's observation or preference...">{{ old('note') }}</textarea>

                        <div class="note-example rounded px-4 py-3 mt-3">

                            <div class="text-muted fs-8">
                                <i class="bi bi-stars text-primary me-1"></i>

                                Example:
                                Sensitive scalp, prefers senior stylist,
                                Saturday mornings and low-fragrance products.
                            </div>

                        </div>

                        @error('note')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Marketing Consent --}}
                    <div class="col-12">

                        <label class="consent-card d-block p-5 cursor-pointer">

                            <div class="d-flex align-items-center">

                                <span class="customer-section-icon bg-light-success me-4">
                                    <i class="bi bi-megaphone fs-3 text-success"></i>
                                </span>

                                <div class="flex-grow-1">

                                    <div class="d-flex align-items-center mb-1">

                                        <span class="fw-bold text-gray-900 me-2">
                                            Marketing Consent
                                        </span>

                                        <span class="badge badge-light">
                                            Optional
                                        </span>

                                    </div>

                                    <div class="text-muted fs-8">
                                        Customer agrees to receive salon promotions,
                                        offers and marketing communications.
                                    </div>

                                </div>


                                <div class="form-check form-switch form-check-custom form-check-solid ms-4">

                                    <input class="form-check-input h-25px w-45px" type="checkbox"
                                        name="marketing_consent" value="1" @checked(old('marketing_consent', $customer->marketing_consent ?? false))>

                                </div>

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


        {{-- CUSTOMER PREVIEW --}}
        <div class="card border-0 shadow-sm customer-preview mb-7">

            <div class="card-body position-relative text-center p-7">

                <div class="customer-avatar mx-auto mb-4">
                    {{ $customerInitials }}
                </div>

                <h3 class="fw-bold text-gray-900 mb-1" id="customer_preview_name">
                    {{ trim(old('first_name', $customer->first_name) . ' ' . old('last_name', $customer->last_name)) ?:
                        'New Customer' }}
                </h3>


                @if ($isEdit)
                    <div class="text-muted fs-8 mb-3">
                        {{ $customer->customer_code }}
                    </div>
                @else
                    <div class="text-muted fs-8 mb-3">
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
        <div class="card border-0 shadow-sm customer-form-card position-sticky sticky-customer-panel">

            <div class="card-header border-0">

                <div class="card-title">

                    <span class="customer-section-icon bg-light-primary me-4">
                        <i class="bi bi-shop-window fs-2 text-primary"></i>
                    </span>

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


            <div class="card-body pt-3">

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

                    <div class="text-muted fs-8 mt-2">
                        Used as this customer's preferred or default salon branch.
                    </div>

                    @error('branch_id')
                        <div class="text-danger fs-7 mt-2">
                            {{ $message }}
                        </div>
                    @enderror

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
                        <div class="text-danger fs-7 mt-2">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Profile Summary --}}
                <div class="customer-help-box p-4">

                    <div class="fw-bold text-gray-900 mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Profile Summary
                    </div>


                    <div class="customer-info-row">

                        <span class="text-muted fs-8">
                            Mode
                        </span>

                        <span class="badge badge-light-primary">
                            {{ $isEdit ? 'Editing' : 'Creating' }}
                        </span>

                    </div>


                    <div class="customer-info-row">

                        <span class="text-muted fs-8">
                            Branches Available
                        </span>

                        <span class="fw-bold text-gray-900">
                            {{ $branches->count() }}
                        </span>

                    </div>


                    <div class="customer-info-row">

                        <span class="text-muted fs-8">
                            Marketing
                        </span>

                        <span class="fw-semibold text-gray-800">
                            Optional
                        </span>

                    </div>

                </div>

            </div>


            {{-- Actions --}}
            <div class="card-footer customer-save-bar">

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

                const url = new URL(createUrl, window.location.origin);

                url.searchParams.set(
                    'tenant_id',
                    this.value
                );

                window.location.href = url.toString();

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
                    ?.trim() ?? this.value;

                statusPreview.innerHTML =
                    `<i class="bi bi-circle-fill fs-10 me-2"></i>${statusText}`;

            });

        });
    </script>
@endpush
