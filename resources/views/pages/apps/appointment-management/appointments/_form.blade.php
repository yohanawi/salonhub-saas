@php
    $isEdit = $appointment->exists;
    $serviceRows = old('services');

    if (!$serviceRows) {
        $serviceRows = $isEdit
            ? $appointment->appointmentServices
                ->map(
                    fn($item) => [
                        'service_id' => $item->service_id,
                        'staff_id' => $item->staff_id,
                        'discount_amount' => $item->discount_amount,
                    ],
                )
                ->values()
                ->all()
            : [
                [
                    'service_id' => '',
                    'staff_id' => '',
                    'discount_amount' => 0,
                ],
            ];
    }
@endphp


{{-- ============================================================
    LOCAL PAGE STYLES
============================================================ --}}
<style>
    .appointment-workspace {
        --booking-primary: #7239ea;
        --booking-soft: #f5f3ff;
        --booking-border: #edf0f5;
    }

    .booking-card {
        border: 1px solid var(--booking-border) !important;
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .booking-section-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg,
                rgba(114, 57, 234, .12),
                rgba(0, 158, 247, .08));
    }

    .booking-section-number {
        display: inline-flex;
        width: 25px;
        height: 25px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: var(--booking-soft);
        color: var(--booking-primary);
        font-size: .72rem;
        font-weight: 800;
    }

    .booking-field {
        position: relative;
    }

    .booking-field .form-label {
        margin-bottom: .65rem;
    }

    .booking-field .form-control-solid,
    .booking-field .form-select-solid {
        min-height: 48px;
        border-radius: 12px;
    }

    .service-row-card {
        position: relative;
        border: 1px solid #edf0f5;
        border-radius: 18px;
        background:
            linear-gradient(145deg,
                #ffffff 0%,
                #fbfbff 100%);
        transition:
            border-color .2s ease,
            box-shadow .2s ease,
            transform .2s ease;
    }

    .service-row-card:hover {
        border-color: rgba(114, 57, 234, .2);
        box-shadow: 0 10px 30px rgba(31, 41, 55, .05);
    }

    .service-row-number {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--booking-soft);
        color: var(--booking-primary);
        font-size: .85rem;
        font-weight: 800;
    }

    .service-row-drag {
        color: #b5b5c3;
        cursor: default;
    }

    .service-row-divider {
        height: 1px;
        background: #f0f1f5;
    }

    .review-card {
        overflow: hidden;
        border: 1px solid #edf0f5 !important;
    }

    .review-card-header {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 95% 10%,
                rgba(114, 57, 234, .13),
                transparent 35%),
            linear-gradient(135deg,
                #ffffff,
                #fbfaff);
    }

    .review-card-header::after {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        right: -45px;
        top: -65px;
        background: rgba(114, 57, 234, .04);
    }

    .review-info-box {
        padding: 14px 16px;
        border-radius: 13px;
        background: #f8f9fc;
    }

    .review-tip {
        border: 1px dashed rgba(114, 57, 234, .25);
        border-radius: 14px;
        background: rgba(114, 57, 234, .035);
    }

    .appointment-save-btn {
        min-height: 52px;
        border-radius: 13px;
        box-shadow: 0 10px 25px rgba(114, 57, 234, .18);
    }

    .appointment-cancel-btn {
        min-height: 48px;
        border-radius: 13px;
    }

    .service-empty-text {
        font-size: .75rem;
    }

    .service-filter-hint {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 7px;
        color: #a1a5b7;
        font-size: .72rem;
    }

    .booking-alert {
        border-radius: 16px;
    }

    @media (max-width: 1199.98px) {
        .appointment-review-sticky {
            position: static !important;
        }
    }
</style>


<div class="appointment-workspace">

    {{-- ============================================================
        VALIDATION
    ============================================================ --}}
    @if ($errors->any())

        <div
            class="alert alert-danger booking-alert border-0 shadow-sm
                    d-flex align-items-start mb-8">

            <div class="symbol symbol-50px me-4 flex-shrink-0">

                <span class="symbol-label bg-light-danger rounded-4">
                    <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
                </span>

            </div>

            <div class="flex-grow-1">

                <div class="fw-bolder text-gray-900 fs-6 mb-1">
                    Please review the appointment
                </div>

                <div class="text-gray-700">
                    {{ $errors->first() }}
                </div>

                @if ($errors->count() > 1)
                    <div class="text-muted fs-8 mt-2">
                        {{ $errors->count() }} validation issues require attention.
                    </div>
                @endif

            </div>

        </div>

    @endif


    <div class="row g-8">

        {{-- ========================================================
            LEFT SIDE
        ======================================================== --}}
        <div class="col-xl-8">


            {{-- ====================================================
                BOOKING DETAILS
            ==================================================== --}}
            <div class="card booking-card border-0 shadow-sm mb-8">

                <div class="card-header border-0 pt-8 pb-2">

                    <div class="card-title">

                        <div class="d-flex align-items-center gap-4">

                            <div class="booking-section-icon">
                                <i class="bi bi-calendar2-check-fill text-primary fs-2"></i>
                            </div>

                            <div>

                                <div class="d-flex align-items-center gap-2 mb-1">

                                    <span class="booking-section-number">
                                        01
                                    </span>

                                    <span class="text-muted fw-semibold fs-8 text-uppercase">
                                        Appointment Setup
                                    </span>

                                </div>

                                <h2 class="fw-bolder text-gray-900 mb-1">
                                    Booking Details
                                </h2>

                                <div class="text-muted fs-7">
                                    Choose where, when and for whom this appointment
                                    will be scheduled.
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-body pt-5">

                    <div class="row g-6">


                        {{-- Salon / Tenant --}}
                        @if (($tenants ?? collect())->isNotEmpty())

                            <div class="col-12 booking-field">

                                <label class="form-label required fw-semibold text-gray-800">
                                    Salon / Tenant
                                </label>

                                <select name="tenant_id" id="appointment_tenant_id"
                                    class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                                    data-placeholder="Select salon" required>
                                    <option value="">
                                        Select salon
                                    </option>

                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach

                                </select>

                                <div class="service-filter-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Selecting another salon reloads its available
                                    booking resources.
                                </div>

                            </div>

                        @endif


                        {{-- Customer --}}
                        <div class="col-lg-6 booking-field">

                            <label class="form-label required fw-semibold text-gray-800">

                                <i class="bi bi-person me-1 text-muted"></i>
                                Customer

                            </label>

                            <select name="customer_id" class="form-select form-select-solid" data-control="select2"
                                data-placeholder="Search customer" required>

                                <option value="">
                                    Select customer
                                </option>

                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $appointment->customer_id) === (string) $customer->id)>

                                        {{ $customer->full_name }}

                                        @if ($customer->phone)
                                            - {{ $customer->phone }}
                                        @endif

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Branch --}}
                        <div class="col-lg-6 booking-field">

                            <label class="form-label required fw-semibold text-gray-800">

                                <i class="bi bi-geo-alt me-1 text-muted"></i>
                                Branch

                            </label>

                            <select name="branch_id" id="appointment_branch_id" class="form-select form-select-solid"
                                data-control="select2" data-hide-search="true" data-placeholder="Select branch"
                                required>

                                <option value="">
                                    Select branch
                                </option>

                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $appointment->branch_id) === (string) $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach

                            </select>

                            <div class="service-filter-hint">
                                <i class="bi bi-person-check"></i>
                                Staff availability is filtered using this branch.
                            </div>

                        </div>


                        <div class="col-12">
                            <div class="service-row-divider"></div>
                        </div>


                        {{-- Booking Source --}}
                        <div class="col-lg-4 booking-field">

                            <label class="form-label required fw-semibold text-gray-800">
                                Booking Source
                            </label>

                            <select name="booking_source" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true" required>

                                @foreach ($bookingSources as $source)
                                    <option value="{{ $source }}" @selected(old('booking_source', $appointment->booking_source ?? 'reception') === $source)>
                                        {{ str($source)->replace('_', ' ')->headline() }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Status --}}
                        <div class="col-lg-4 booking-field">

                            <label class="form-label required fw-semibold text-gray-800">
                                Appointment Status
                            </label>

                            <select name="status" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true" required>

                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', $appointment->status ?? \App\Models\Appointment::STATUS_PENDING) === $status)>
                                        {{ str($status)->replace('_', ' ')->headline() }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Start --}}
                        <div class="col-lg-4 booking-field">

                            <label class="form-label required fw-semibold text-gray-800">

                                <i class="bi bi-clock me-1 text-muted"></i>
                                Start Date & Time

                            </label>

                            <input type="datetime-local" name="starts_at" id="appointment_starts_at"
                                value="{{ old('starts_at', optional($appointment->starts_at)->format('Y-m-d\TH:i')) }}"
                                class="form-control form-control-solid" required>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ====================================================
                SERVICES
            ==================================================== --}}
            <div class="card booking-card border-0 shadow-sm mb-8">

                <div class="card-header border-0 pt-8 pb-2">

                    <div class="card-title">

                        <div class="d-flex align-items-center gap-4">

                            <div class="booking-section-icon">
                                <i class="bi bi-stars text-primary fs-2"></i>
                            </div>

                            <div>

                                <div class="d-flex align-items-center gap-2 mb-1">

                                    <span class="booking-section-number">
                                        02
                                    </span>

                                    <span class="text-muted fw-semibold fs-8 text-uppercase">
                                        Treatment Selection
                                    </span>

                                </div>

                                <h2 class="fw-bolder text-gray-900 mb-1">
                                    Services & Staff
                                </h2>

                                <div class="text-muted fs-7">
                                    Build the appointment by assigning each treatment
                                    to an eligible team member.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-toolbar">

                        <button type="button" class="btn btn-light-primary btn-sm px-4" id="add-service-row">
                            <i class="bi bi-plus-lg me-2"></i>
                            Add Service
                        </button>

                    </div>

                </div>


                <div class="card-body pt-5">

                    <div id="service-rows" class="d-flex flex-column gap-5">

                        @foreach ($serviceRows as $index => $row)
                            <div class="appointment-service-row service-row-card p-5 p-lg-6" data-service-row>

                                {{-- Service Row Header --}}
                                <div
                                    class="d-flex justify-content-between
                                            align-items-center gap-4 mb-5">

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="service-row-number" data-service-number>
                                            {{ $index + 1 }}
                                        </div>

                                        <div>

                                            <div class="fw-bold text-gray-900">
                                                Service <span data-service-label>{{ $index + 1 }}</span>
                                            </div>

                                            <div class="text-muted fs-8">
                                                Select treatment, team member and
                                                optional discount.
                                            </div>

                                        </div>

                                    </div>


                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-light-danger
                                               remove-service-row"
                                        title="Remove service">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </div>


                                <div class="row g-5 align-items-start">


                                    {{-- Service --}}
                                    <div class="col-lg-5 booking-field">

                                        <label class="form-label required fw-semibold">
                                            Service
                                        </label>

                                        <select name="services[{{ $index }}][service_id]"
                                            data-control="select2" data-hide-search="true"
                                            class="form-select form-select-solid service-select" required>

                                            <option value="">
                                                Select service
                                            </option>

                                            @foreach ($services as $service)
                                                @php
                                                    $serviceDuration =
                                                        $service->default_duration_minutes ??
                                                        $service->duration_minutes;

                                                    $servicePrice = $service->default_price ?? $service->price;
                                                @endphp

                                                <option value="{{ $service->id }}"
                                                    data-service-name="{{ $service->name }}"
                                                    data-duration="{{ $serviceDuration }}"
                                                    data-price="{{ $servicePrice }}" @selected((string) ($row['service_id'] ?? '') === (string) $service->id)>
                                                    {{ $service->name }}
                                                    • {{ $serviceDuration }} min
                                                    • LKR
                                                    {{ number_format((float) $servicePrice, 2) }}
                                                </option>
                                            @endforeach

                                        </select>

                                        <div class="service-filter-hint">
                                            <i class="bi bi-clock"></i>
                                            Duration and price are recalculated by
                                            the server.
                                        </div>

                                    </div>


                                    {{-- Staff --}}
                                    <div class="col-lg-5 booking-field">

                                        <label class="form-label required fw-semibold">
                                            Staff Member
                                        </label>

                                        <select name="services[{{ $index }}][staff_id]" data-control="select2"
                                            data-hide-search="true" class="form-select form-select-solid staff-select"
                                            required>

                                            <option value="">
                                                Select staff
                                            </option>

                                            @foreach ($staff as $member)
                                                <option value="{{ $member->id }}"
                                                    data-branches="{{ $member->branches->pluck('id')->implode(',') }}"
                                                    data-services="{{ $member->services->pluck('id')->implode(',') }}"
                                                    @selected((string) ($row['staff_id'] ?? '') === (string) $member->id)>

                                                    {{ $member->full_name }}

                                                    @if ($member->job_title)
                                                        - {{ $member->job_title }}
                                                    @endif

                                                </option>
                                            @endforeach

                                        </select>

                                        <div class="service-filter-hint">
                                            <i class="bi bi-funnel"></i>
                                            Only staff qualified for the selected
                                            branch and service remain available.
                                        </div>

                                    </div>


                                    {{-- Discount --}}
                                    <div class="col-lg-2 booking-field">

                                        <label class="form-label fw-semibold">
                                            Discount
                                        </label>

                                        <div class="input-group input-group-solid">

                                            <span class="input-group-text">
                                                LKR
                                            </span>

                                            <input type="number" min="0" step="0.01"
                                                name="services[{{ $index }}][discount_amount]"
                                                value="{{ $row['discount_amount'] ?? 0 }}"
                                                class="form-control service-discount-input">

                                        </div>

                                    </div>

                                </div>


                                {{-- Inline Preview --}}
                                <div class="service-preview mt-5 pt-4 border-top">

                                    <div class="d-flex flex-wrap gap-4 text-muted fs-8">

                                        <span>
                                            <i class="bi bi-scissors me-1"></i>
                                            <span data-preview-service>
                                                Select a service
                                            </span>
                                        </span>

                                        <span>
                                            <i class="bi bi-person me-1"></i>
                                            <span data-preview-staff>
                                                Select a staff member
                                            </span>
                                        </span>

                                        <span>
                                            <i class="bi bi-tag me-1"></i>
                                            Discount:
                                            LKR
                                            <span data-preview-discount>
                                                {{ number_format((float) ($row['discount_amount'] ?? 0), 2) }}
                                            </span>
                                        </span>

                                    </div>

                                </div>

                            </div>
                        @endforeach

                    </div>


                    {{-- Add another large action --}}
                    <button type="button" id="add-service-row-secondary"
                        class="btn btn-light-primary border border-dashed
                               w-100 mt-5 py-4">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Another Service
                    </button>

                </div>

            </div>

        </div>



        {{-- ========================================================
            RIGHT SIDE REVIEW
        ======================================================== --}}
        <div class="col-xl-4">

            <div class="card review-card border-0 shadow-sm
                       appointment-review-sticky position-sticky"
                style="top: 100px;">

                {{-- Header --}}
                <div class="review-card-header px-7 pt-8 pb-7">

                    <div class="position-relative">

                        <div class="d-flex align-items-center gap-4 mb-4">

                            <div class="symbol symbol-50px">

                                <span class="symbol-label bg-light-primary rounded-4">
                                    <i class="bi bi-receipt-cutoff text-primary fs-2"></i>
                                </span>

                            </div>

                            <div>

                                <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">
                                    Appointment Summary
                                </div>

                                <h2 class="fw-bolder text-gray-900 mb-0">
                                    Review Booking
                                </h2>

                            </div>

                        </div>


                        <div class="text-muted fs-7">
                            Final duration, pricing and staff availability are
                            validated again when the appointment is saved.
                        </div>

                    </div>

                </div>


                <div class="card-body pt-6">


                    {{-- Quick Summary --}}
                    <div class="review-info-box mb-6">

                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <span class="text-muted fs-7">
                                Services
                            </span>

                            <span class="badge badge-light-primary" id="appointment-service-count">
                                {{ count($serviceRows) }}
                            </span>

                        </div>


                        <div class="d-flex justify-content-between align-items-center">

                            <span class="text-muted fs-7">
                                Mode
                            </span>

                            <span class="fw-bold text-gray-900 fs-7">
                                {{ $isEdit ? 'Editing Appointment' : 'New Appointment' }}
                            </span>

                        </div>

                    </div>



                    {{-- Appointment discount --}}
                    <div class="booking-field mb-6">

                        <label class="form-label fw-semibold text-gray-800">

                            <i class="bi bi-percent me-1 text-muted"></i>
                            Appointment Discount

                        </label>

                        <div class="input-group input-group-solid">

                            <span class="input-group-text">
                                LKR
                            </span>

                            <input type="number" min="0" step="0.01" name="discount_amount"
                                value="{{ old('discount_amount', $appointment->discount_amount ?? 0) }}"
                                class="form-control">

                        </div>

                        <div class="text-muted fs-8 mt-2">
                            Applied after service-level discounts.
                        </div>

                    </div>



                    {{-- Tax --}}
                    <div class="booking-field mb-6">

                        <label class="form-label fw-semibold text-gray-800">

                            <i class="bi bi-receipt me-1 text-muted"></i>
                            Tax Amount

                        </label>

                        <div class="input-group input-group-solid">

                            <span class="input-group-text">
                                LKR
                            </span>

                            <input type="number" min="0" step="0.01" name="tax_amount"
                                value="{{ old('tax_amount', $appointment->tax_amount ?? 0) }}" class="form-control">

                        </div>

                    </div>



                    <div class="separator separator-dashed my-7"></div>



                    {{-- Customer notes --}}
                    <div class="booking-field mb-6">

                        <label class="form-label fw-semibold text-gray-800">

                            <i class="bi bi-chat-left-text me-1 text-muted"></i>
                            Customer Notes

                        </label>

                        <textarea name="customer_notes" class="form-control form-control-solid" rows="4"
                            placeholder="Preferences, requests, allergies, important customer information...">{{ old('customer_notes', $appointment->customer_notes ?? $appointment->notes) }}</textarea>

                        <div class="text-muted fs-8 mt-2">
                            Notes relevant to serving this customer.
                        </div>

                    </div>



                    {{-- Internal Notes --}}
                    @if (auth()->user()?->can('appointments.manage_internal_notes') || auth()->user()?->hasRole('Super Admin'))
                        <div class="booking-field mb-7">

                            <label class="form-label fw-semibold text-gray-800">

                                <i class="bi bi-shield-lock me-1 text-muted"></i>
                                Internal Notes

                            </label>

                            <textarea name="internal_notes" class="form-control form-control-solid" rows="4"
                                placeholder="Private notes for salon staff...">{{ old('internal_notes', $appointment->internal_notes) }}</textarea>

                            <div
                                class="d-flex align-items-center gap-2
                                        text-warning fs-8 mt-2">

                                <i class="bi bi-lock-fill"></i>

                                Internal only — not intended for the customer.

                            </div>

                        </div>
                    @endif



                    {{-- Backend reminder --}}
                    <div class="review-tip p-4 mb-7">

                        <div class="d-flex align-items-start gap-3">

                            <i class="bi bi-shield-check text-primary fs-4 mt-1"></i>

                            <div>

                                <div class="fw-bold text-gray-800 fs-7 mb-1">
                                    Server-side validation
                                </div>

                                <div class="text-muted fs-8">
                                    Service duration, pricing, staff eligibility
                                    and booking availability are recalculated
                                    before saving.
                                </div>

                            </div>

                        </div>

                    </div>



                    {{-- Actions --}}
                    <div class="d-grid gap-3">

                        <button type="submit" class="btn btn-primary btn-lg appointment-save-btn">

                            <i class="bi {{ $isEdit ? 'bi-check2-circle' : 'bi-calendar2-plus' }} me-2"></i>

                            {{ $isEdit ? 'Save Appointment' : 'Create Appointment' }}

                        </button>


                        <a href="{{ $isEdit
                            ? route('appointment-management.appointments.show', $appointment)
                            : route('appointment-management.appointments.index') }}"
                            class="btn btn-light appointment-cancel-btn
                                   d-flex align-items-center justify-content-center">

                            <i class="bi bi-arrow-left me-2"></i>
                            Cancel

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ============================================================
    DYNAMIC SERVICE ROWS
============================================================ --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const tenantSelect =
                document.getElementById('appointment_tenant_id');

            const branchSelect =
                document.getElementById('appointment_branch_id');

            const rows =
                document.getElementById('service-rows');

            const addButton =
                document.getElementById('add-service-row');

            const secondaryAddButton =
                document.getElementById('add-service-row-secondary');

            const serviceCount =
                document.getElementById('appointment-service-count');


            /*
            |--------------------------------------------------------------------------
            | Tenant Reload
            |--------------------------------------------------------------------------
            */
            tenantSelect?.addEventListener('change', function() {

                if (!this.value) {
                    return;
                }

                const url = new URL(
                    @json(route('appointment-management.appointments.create')),
                    window.location.origin
                );

                url.searchParams.set('tenant_id', this.value);

                window.location.href = url.toString();

            });



            /*
            |--------------------------------------------------------------------------
            | Refresh staff eligibility
            |--------------------------------------------------------------------------
            */
            function refreshStaffOptions(row) {

                const serviceId =
                    row.querySelector('.service-select')?.value;

                const branchId =
                    branchSelect?.value;

                const staffSelect =
                    row.querySelector('.staff-select');

                if (!staffSelect) {
                    return;
                }


                staffSelect
                    .querySelectorAll('option')
                    .forEach(function(option) {

                        if (!option.value) {
                            option.hidden = false;
                            option.disabled = false;
                            return;
                        }

                        const branches =
                            (option.dataset.branches || '')
                            .split(',')
                            .filter(Boolean);

                        const services =
                            (option.dataset.services || '')
                            .split(',')
                            .filter(Boolean);


                        const invalidBranch =
                            Boolean(branchId) &&
                            !branches.includes(branchId);

                        const invalidService =
                            Boolean(serviceId) &&
                            !services.includes(serviceId);


                        const shouldHide =
                            invalidBranch || invalidService;


                        option.hidden = shouldHide;
                        option.disabled = shouldHide;


                        if (shouldHide && option.selected) {
                            staffSelect.value = '';
                        }

                    });


                /*
                 * If this select is initialized with Select2,
                 * tell it its value may have changed.
                 */
                if (window.jQuery && jQuery(staffSelect).data('select2')) {
                    jQuery(staffSelect).trigger('change.select2');
                }

            }



            /*
            |--------------------------------------------------------------------------
            | Service Preview
            |--------------------------------------------------------------------------
            */
            function updateRowPreview(row) {

                const serviceSelect =
                    row.querySelector('.service-select');

                const staffSelect =
                    row.querySelector('.staff-select');

                const discountInput =
                    row.querySelector('.service-discount-input');


                const previewService =
                    row.querySelector('[data-preview-service]');

                const previewStaff =
                    row.querySelector('[data-preview-staff]');

                const previewDiscount =
                    row.querySelector('[data-preview-discount]');


                const selectedService =
                    serviceSelect?.options[
                        serviceSelect.selectedIndex
                    ];

                const selectedStaff =
                    staffSelect?.options[
                        staffSelect.selectedIndex
                    ];


                if (previewService) {
                    previewService.textContent =
                        selectedService?.value ?
                        (
                            selectedService.dataset.serviceName ||
                            selectedService.textContent.trim()
                        ) :
                        'Select a service';
                }


                if (previewStaff) {
                    previewStaff.textContent =
                        selectedStaff?.value ?
                        selectedStaff.textContent.trim() :
                        'Select a staff member';
                }


                if (previewDiscount) {

                    const value =
                        parseFloat(discountInput?.value || 0);

                    previewDiscount.textContent =
                        Number.isFinite(value) ?
                        value.toLocaleString(
                            undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        ) :
                        '0.00';
                }

            }



            /*
            |--------------------------------------------------------------------------
            | Update row indexes + UI
            |--------------------------------------------------------------------------
            */
            function refreshRows() {

                if (!rows) {
                    return;
                }

                const serviceRows =
                    rows.querySelectorAll('[data-service-row]');


                serviceRows.forEach(function(row, index) {

                    /*
                     * Update Laravel array input indexes
                     */
                    row.querySelectorAll('[name]')
                        .forEach(function(input) {

                            input.name =
                                input.name.replace(
                                    /services\[\d+\]/,
                                    'services[' + index + ']'
                                );

                        });


                    /*
                     * Number display
                     */
                    const number =
                        row.querySelector(
                            '[data-service-number]'
                        );

                    const label =
                        row.querySelector(
                            '[data-service-label]'
                        );

                    if (number) {
                        number.textContent = index + 1;
                    }

                    if (label) {
                        label.textContent = index + 1;
                    }


                    /*
                     * Keep at least one service
                     */
                    const removeButton =
                        row.querySelector(
                            '.remove-service-row'
                        );

                    if (removeButton) {

                        removeButton.disabled =
                            serviceRows.length === 1;

                    }


                    refreshStaffOptions(row);
                    updateRowPreview(row);

                });


                if (serviceCount) {
                    serviceCount.textContent =
                        serviceRows.length;
                }

            }



            /*
            |--------------------------------------------------------------------------
            | Clone Service Row
            |--------------------------------------------------------------------------
            */
            function addServiceRow() {

                const firstRow =
                    rows?.querySelector(
                        '[data-service-row]'
                    );

                if (!firstRow) {
                    return;
                }


                const clone =
                    firstRow.cloneNode(true);


                /*
                 * Reset all form controls
                 */
                clone.querySelectorAll(
                    'select, input, textarea'
                ).forEach(function(input) {

                    if (input.matches('select')) {
                        input.value = '';
                        return;
                    }

                    if (input.type === 'number') {

                        input.value =
                            input.classList.contains(
                                'service-discount-input'
                            ) ?
                            '0' :
                            '';

                        return;
                    }

                    input.value = '';

                });


                /*
                 * Remove Select2-generated DOM if any somehow
                 * ended up inside cloned markup.
                 */
                clone.querySelectorAll(
                    '.select2-container'
                ).forEach(function(element) {
                    element.remove();
                });


                /*
                 * Restore select visibility/classes.
                 */
                clone.querySelectorAll('select')
                    .forEach(function(select) {

                        select.removeAttribute(
                            'data-select2-id'
                        );

                        select.classList.remove(
                            'select2-hidden-accessible'
                        );

                        select.removeAttribute(
                            'aria-hidden'
                        );

                        select.removeAttribute(
                            'tabindex'
                        );

                    });


                rows.appendChild(clone);

                refreshRows();

            }



            /*
            |--------------------------------------------------------------------------
            | Add buttons
            |--------------------------------------------------------------------------
            */
            addButton?.addEventListener(
                'click',
                addServiceRow
            );

            secondaryAddButton?.addEventListener(
                'click',
                addServiceRow
            );



            /*
            |--------------------------------------------------------------------------
            | Remove Service
            |--------------------------------------------------------------------------
            */
            rows?.addEventListener(
                'click',
                function(event) {

                    const button =
                        event.target.closest(
                            '.remove-service-row'
                        );

                    if (!button) {
                        return;
                    }


                    const totalRows =
                        rows.querySelectorAll(
                            '[data-service-row]'
                        ).length;


                    if (totalRows <= 1) {
                        return;
                    }


                    button
                        .closest('[data-service-row]')
                        ?.remove();


                    refreshRows();

                }
            );



            /*
            |--------------------------------------------------------------------------
            | Service/Staff Changes
            |--------------------------------------------------------------------------
            */
            rows?.addEventListener(
                'change',
                function(event) {

                    const row =
                        event.target.closest(
                            '[data-service-row]'
                        );

                    if (!row) {
                        return;
                    }


                    if (
                        event.target.classList.contains(
                            'service-select'
                        )
                    ) {
                        refreshStaffOptions(row);
                    }


                    updateRowPreview(row);

                }
            );



            /*
            |--------------------------------------------------------------------------
            | Discount Preview
            |--------------------------------------------------------------------------
            */
            rows?.addEventListener(
                'input',
                function(event) {

                    if (
                        !event.target.classList.contains(
                            'service-discount-input'
                        )
                    ) {
                        return;
                    }


                    const row =
                        event.target.closest(
                            '[data-service-row]'
                        );

                    if (row) {
                        updateRowPreview(row);
                    }

                }
            );



            /*
            |--------------------------------------------------------------------------
            | Branch Change
            |--------------------------------------------------------------------------
            */
            branchSelect?.addEventListener(
                'change',
                function() {

                    rows?.querySelectorAll(
                        '[data-service-row]'
                    ).forEach(function(row) {

                        refreshStaffOptions(row);
                        updateRowPreview(row);

                    });

                }
            );



            /*
            |--------------------------------------------------------------------------
            | Initial State
            |--------------------------------------------------------------------------
            */
            refreshRows();

        });
    </script>
@endpush
