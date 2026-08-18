<x-default-layout>

    @section('title')
        Appointment Profile
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('appointment-management.appointments.show', $appointment) }}
    @endsection


    @php
        $statusClass = match ($appointment->status) {
            'pending' => 'warning',
            'confirmed' => 'primary',
            'checked_in' => 'info',
            'in_progress' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'no_show' => 'dark',
            default => 'secondary',
        };

        $statusIcon = match ($appointment->status) {
            'pending' => 'bi-clock-history',
            'confirmed' => 'bi-check-circle-fill',
            'checked_in' => 'bi-box-arrow-in-right',
            'in_progress' => 'bi-scissors',
            'completed' => 'bi-check2-all',
            'cancelled' => 'bi-x-circle-fill',
            'no_show' => 'bi-person-x-fill',
            default => 'bi-circle-fill',
        };

        $customerName = $appointment->customer?->full_name ?? 'Walk-in Customer';

        $customerInitials = collect(preg_split('/\s+/', trim($customerName)))
            ->filter()
            ->take(2)
            ->map(fn($part) => strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        $serviceCount = $appointment->appointmentServices->count();

        $staffNames = $appointment->appointmentServices->pluck('staff.full_name')->filter()->unique()->values();

        $totalDuration = $appointment->appointmentServices->sum('duration_minutes');
    @endphp


    @push('styles')
        <style>
            .appointment-profile {
                --profile-primary: #7239ea;
                --profile-soft: #f5f2ff;
                --profile-border: #edf0f5;
                --profile-text-muted: #a1a5b7;
            }

            /* =========================================================
                 | HERO
                 * ========================================================= */

            .appointment-profile-hero {
                position: relative;
                overflow: hidden;
                border: 1px solid var(--profile-border) !important;
                background:
                    radial-gradient(circle at 94% 8%,
                        rgba(114, 57, 234, .15),
                        transparent 30%),
                    radial-gradient(circle at 4% 100%,
                        rgba(0, 158, 247, .08),
                        transparent 30%),
                    linear-gradient(135deg,
                        #ffffff 0%,
                        #fafaff 100%);
            }

            .appointment-profile-hero::after {
                content: '';
                position: absolute;
                width: 230px;
                height: 230px;
                border-radius: 50%;
                right: -110px;
                top: -135px;
                border: 35px solid rgba(114, 57, 234, .035);
            }

            .appointment-main-icon {
                width: 74px;
                height: 74px;
                min-width: 74px;
                border-radius: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg,
                        #f2edff,
                        #eaf6ff);
                box-shadow: 0 14px 36px rgba(114, 57, 234, .12);
            }

            .appointment-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 12px;
                border-radius: 10px;
            }

            /* =========================================================
                 | HERO STATS
                 * ========================================================= */

            .appointment-quick-stat {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .appointment-quick-stat-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* =========================================================
                 | CARDS
                 * ========================================================= */

            .appointment-section-card {
                border: 1px solid var(--profile-border) !important;
            }

            .appointment-section-icon {
                width: 46px;
                height: 46px;
                min-width: 46px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--profile-soft);
            }

            /* =========================================================
                 | SERVICE CARDS
                 * ========================================================= */

            .service-profile-card {
                position: relative;
                overflow: hidden;
                padding: 20px;
                border: 1px solid var(--profile-border);
                border-radius: 17px;
                background:
                    linear-gradient(145deg,
                        #ffffff,
                        #fcfcff);
                transition:
                    border-color .2s ease,
                    transform .2s ease,
                    box-shadow .2s ease;
            }

            .service-profile-card:hover {
                transform: translateY(-2px);
                border-color: rgba(114, 57, 234, .2);
                box-shadow: 0 12px 32px rgba(31, 41, 55, .06);
            }

            .service-profile-card::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: var(--profile-primary);
            }

            .service-profile-icon {
                width: 44px;
                height: 44px;
                min-width: 44px;
                border-radius: 13px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--profile-soft);
            }

            .service-info-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 9px;
                border-radius: 8px;
                background: #f7f8fb;
                color: #5e6278;
                font-size: .72rem;
                font-weight: 600;
            }

            /* =========================================================
                 | TOTALS
                 * ========================================================= */

            .appointment-total-box {
                max-width: 390px;
                margin-left: auto;
                padding: 20px;
                border-radius: 16px;
                background:
                    linear-gradient(135deg,
                        #fafaff,
                        #f7f5ff);
                border: 1px solid rgba(114, 57, 234, .1);
            }

            .appointment-total-row {
                display: flex;
                justify-content: space-between;
                gap: 24px;
                padding: 7px 0;
            }

            .appointment-grand-total {
                margin-top: 8px;
                padding-top: 14px;
                border-top: 1px dashed #dfe2e8;
            }

            /* =========================================================
                 | TIMELINE
                 * ========================================================= */

            .status-timeline {
                position: relative;
            }

            .status-history-item {
                position: relative;
                display: flex;
                gap: 16px;
                padding-bottom: 26px;
            }

            .status-history-item:not(:last-child)::before {
                content: '';
                position: absolute;
                left: 18px;
                top: 36px;
                bottom: -3px;
                width: 2px;
                background: #edf0f5;
            }

            .timeline-marker {
                position: relative;
                z-index: 2;
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--profile-soft);
                color: var(--profile-primary);
            }

            .timeline-content {
                flex: 1;
                padding: 3px 0;
            }

            /* =========================================================
                 | BOOKING SUMMARY
                 * ========================================================= */

            .booking-summary-card {
                overflow: hidden;
                border: 1px solid var(--profile-border) !important;
            }

            .booking-summary-header {
                position: relative;
                overflow: hidden;
                background:
                    radial-gradient(circle at 100% 0%,
                        rgba(114, 57, 234, .1),
                        transparent 38%),
                    #ffffff;
            }

            .customer-profile-avatar {
                width: 58px;
                height: 58px;
                min-width: 58px;
                border-radius: 17px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg,
                        #f1ecff,
                        #e9f6ff);
                color: var(--profile-primary);
                font-weight: 800;
                font-size: 1rem;
            }

            .summary-row {
                display: flex;
                gap: 14px;
                padding: 15px 0;
                border-bottom: 1px dashed #edf0f5;
            }

            .summary-row:last-child {
                border-bottom: 0;
            }

            .summary-row-icon {
                width: 36px;
                height: 36px;
                min-width: 36px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fc;
                color: #7e8299;
            }

            /* =========================================================
                 | NOTES
                 * ========================================================= */

            .appointment-note-box {
                padding: 15px;
                border-radius: 13px;
                background: #f8f9fc;
            }

            .internal-note-box {
                padding: 15px;
                border-radius: 13px;
                background: #fff8e8;
                border: 1px dashed rgba(255, 199, 0, .3);
            }

            /* =========================================================
                 | ACTIONS
                 * ========================================================= */

            .status-action-card {
                border: 1px solid var(--profile-border) !important;
            }

            .workflow-step {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 18px;
                padding: 12px 14px;
                border-radius: 12px;
                background: #f8f9fc;
            }

            .workflow-dot {
                width: 9px;
                height: 9px;
                border-radius: 50%;
                background: var(--profile-primary);
            }

            .status-action-btn {
                min-height: 48px;
                border-radius: 12px;
            }

            .cancellation-box {
                padding: 15px;
                border-radius: 14px;
                background: #fff8fa;
                border: 1px dashed rgba(241, 65, 108, .25);
            }

            @media (max-width: 1199.98px) {
                .appointment-profile-sticky {
                    position: static !important;
                }
            }

            @media (max-width: 767.98px) {
                .appointment-main-icon {
                    width: 60px;
                    height: 60px;
                    min-width: 60px;
                    border-radius: 18px;
                }
            }
        </style>
    @endpush


    <div id="kt_app_content_container" class="appointment-profile">

        {{-- ============================================================
            ALERTS
        ============================================================ --}}

        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm
                        d-flex align-items-center mb-8">

                <div class="symbol symbol-45px me-4">

                    <span class="symbol-label
                                 bg-success bg-opacity-10 rounded-3">

                        <i class="bi bi-check-circle-fill
                                  text-success fs-2"></i>

                    </span>

                </div>

                <div class="flex-grow-1">

                    <div class="fw-bold text-gray-900 mb-1">
                        Appointment updated
                    </div>

                    <div class="text-gray-700">
                        {{ session('status') }}
                    </div>

                </div>

            </div>
        @endif


        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm
                        d-flex align-items-center mb-8">

                <div class="symbol symbol-45px me-4">

                    <span class="symbol-label
                                 bg-danger bg-opacity-10 rounded-3">

                        <i
                            class="bi bi-exclamation-triangle-fill
                                  text-danger fs-2"></i>

                    </span>

                </div>

                <div class="flex-grow-1">

                    <div class="fw-bold text-gray-900 mb-1">
                        Unable to process appointment
                    </div>

                    <div class="text-gray-700">
                        {{ $errors->first() }}
                    </div>

                </div>

            </div>
        @endif



        {{-- ============================================================
            HERO
        ============================================================ --}}

        <div class="card appointment-profile-hero
                    border-0 shadow-sm mb-8">

            <div class="card-body p-8 p-lg-10 position-relative">

                <div
                    class="d-flex flex-column flex-xl-row
                            justify-content-between
                            align-items-xl-center gap-7">

                    <div class="d-flex align-items-center gap-5">

                        <div class="appointment-main-icon">

                            <i
                                class="bi bi-calendar2-check-fill
                                      text-primary fs-1"></i>

                        </div>


                        <div>

                            <div
                                class="d-flex flex-wrap
                                        align-items-center gap-3 mb-2">

                                <h1
                                    class="fs-2hx fw-bolder
                                           text-gray-900 mb-0">

                                    {{ $appointment->appointment_number }}

                                </h1>


                                <span
                                    class="badge badge-light-{{ $statusClass }}
                                           appointment-status-badge">

                                    <i class="bi {{ $statusIcon }}"></i>

                                    {{ $appointment->status_label }}

                                </span>

                            </div>


                            <div
                                class="d-flex flex-wrap gap-4
                                        align-items-center text-muted">

                                <span>

                                    <i
                                        class="bi bi-calendar3
                                              me-1 text-primary"></i>

                                    {{ $appointment->starts_at?->format('l, M d, Y') }}

                                </span>


                                <span>

                                    <i
                                        class="bi bi-clock
                                              me-1 text-primary"></i>

                                    {{ $appointment->starts_at?->format('h:i A') }}

                                    @if ($appointment->ends_at)
                                        –
                                        {{ $appointment->ends_at->format('h:i A') }}
                                    @endif

                                </span>

                            </div>

                        </div>

                    </div>


                    <div class="d-flex flex-column
                                flex-sm-row gap-3">

                        @if ($appointment->status === \App\Models\Appointment::STATUS_COMPLETED && auth()->user()?->can('checkout', \App\Models\Invoice::class))
                            @if ($appointment->invoice)
                                <a href="{{ route('billing.invoices.show', $appointment->invoice) }}"
                                    class="btn btn-success px-6">
                                    <i class="bi bi-receipt me-2"></i>
                                    View Invoice
                                </a>
                            @else
                                <a href="{{ route('billing.checkout.appointments.create', $appointment) }}"
                                    class="btn btn-success px-6">
                                    <i class="bi bi-cash-register me-2"></i>
                                    Checkout Appointment
                                </a>
                            @endif
                        @endif

                        @can('update', $appointment)
                            <a href="{{ route('appointment-management.appointments.edit', $appointment) }}"
                                class="btn btn-light-primary px-6">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Appointment
                            </a>
                        @endcan


                        <a href="{{ route('appointment-management.appointments.index') }}"
                            class="btn btn-light px-6">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to List
                        </a>

                    </div>

                </div>



                {{-- Quick Info --}}
                <div class="d-flex flex-wrap gap-8
                            mt-8 pt-6 border-top">

                    <div class="appointment-quick-stat">

                        <div class="appointment-quick-stat-icon
                                    bg-light-primary">

                            <i class="bi bi-stars text-primary"></i>

                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Services
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $serviceCount }}
                            </div>

                        </div>

                    </div>


                    <div class="appointment-quick-stat">

                        <div class="appointment-quick-stat-icon
                                    bg-light-info">

                            <i class="bi bi-stopwatch text-info"></i>

                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Duration
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $totalDuration }} min
                            </div>

                        </div>

                    </div>


                    <div class="appointment-quick-stat">

                        <div class="appointment-quick-stat-icon
                                    bg-light-success">

                            <i class="bi bi-people text-success"></i>

                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Staff
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $staffNames->count() }}
                            </div>

                        </div>

                    </div>


                    <div class="appointment-quick-stat">

                        <div class="appointment-quick-stat-icon
                                    bg-light-warning">

                            <i class="bi bi-cash-stack
                                      text-warning"></i>

                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Total
                            </div>

                            <div class="fw-bolder text-gray-900">
                                LKR
                                {{ number_format((float) $appointment->total_amount, 2) }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <div class="row g-8">

            {{-- ========================================================
                LEFT CONTENT
            ======================================================== --}}

            <div class="col-xl-8">


                {{-- ====================================================
                    SERVICES
                ==================================================== --}}

                <div class="card appointment-section-card
                            border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8 pb-2">

                        <div class="card-title">

                            <div class="d-flex align-items-center gap-4">

                                <div class="appointment-section-icon">

                                    <i
                                        class="bi bi-stars
                                              text-primary fs-3"></i>

                                </div>

                                <div>

                                    <h3
                                        class="fw-bolder
                                               text-gray-900 mb-1">
                                        Booked Services
                                    </h3>

                                    <div class="text-muted fs-7">
                                        Service, staff, duration and pricing
                                        snapshots captured at booking time.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="card-toolbar">

                            <span class="badge badge-light-primary
                                         px-3 py-2">

                                {{ $serviceCount }}
                                {{ Str::plural('service', $serviceCount) }}

                            </span>

                        </div>

                    </div>


                    <div class="card-body pt-5">

                        <div class="d-flex flex-column gap-4">

                            @foreach ($appointment->appointmentServices as $index => $item)
                                @php
                                    $serviceName = $item->service_name ?: $item->service?->name ?: 'Service';

                                    $staffName = $item->staff?->full_name ?? 'Unassigned';
                                @endphp


                                <div class="service-profile-card">

                                    <div
                                        class="d-flex flex-column
                                                flex-lg-row
                                                justify-content-between
                                                gap-5">

                                        <div class="d-flex gap-4">

                                            <div class="service-profile-icon">

                                                <i
                                                    class="bi bi-scissors
                                                          text-primary fs-4"></i>

                                            </div>


                                            <div>

                                                <div
                                                    class="text-muted
                                                            fs-8 fw-semibold
                                                            text-uppercase
                                                            mb-1">

                                                    Service {{ $index + 1 }}

                                                </div>

                                                <h4
                                                    class="fw-bolder
                                                           text-gray-900
                                                           mb-3">

                                                    {{ $serviceName }}

                                                </h4>


                                                <div
                                                    class="d-flex
                                                            flex-wrap gap-2">

                                                    <span class="service-info-chip">

                                                        <i class="bi bi-person"></i>

                                                        {{ $staffName }}

                                                    </span>


                                                    <span class="service-info-chip">

                                                        <i class="bi bi-clock"></i>

                                                        {{ $item->starts_at?->format('h:i A') }}

                                                        @if ($item->ends_at)
                                                            –
                                                            {{ $item->ends_at->format('h:i A') }}
                                                        @endif

                                                    </span>


                                                    <span class="service-info-chip">

                                                        <i class="bi bi-stopwatch"></i>

                                                        {{ $item->duration_minutes }}
                                                        min

                                                    </span>

                                                </div>


                                                <div class="text-muted fs-8 mt-3">

                                                    <i
                                                        class="bi bi-shield-check
                                                              me-1"></i>

                                                    Booking snapshot retained for
                                                    historical accuracy.

                                                </div>

                                            </div>

                                        </div>


                                        <div class="text-lg-end">

                                            <div class="text-muted fs-8 mb-1">
                                                Service Total
                                            </div>

                                            <div
                                                class="fw-bolder
                                                        text-gray-900 fs-5">

                                                LKR
                                                {{ number_format((float) $item->total_price, 2) }}

                                            </div>

                                            @if ((float) $item->discount_amount > 0)
                                                <div
                                                    class="badge
                                                            badge-light-success
                                                            mt-2">

                                                    Discount:
                                                    LKR
                                                    {{ number_format((float) $item->discount_amount, 2) }}

                                                </div>
                                            @endif

                                        </div>

                                    </div>

                                </div>
                            @endforeach

                        </div>



                        {{-- Financial Summary --}}
                        <div class="separator separator-dashed my-7"></div>


                        <div class="appointment-total-box">

                            <div class="d-flex
                                        align-items-center gap-3 mb-4">

                                <div class="symbol symbol-38px">

                                    <span
                                        class="symbol-label
                                                 bg-light-primary">

                                        <i
                                            class="bi bi-receipt
                                                  text-primary"></i>

                                    </span>

                                </div>

                                <div>

                                    <div class="fw-bold text-gray-900">
                                        Appointment Total
                                    </div>

                                    <div class="text-muted fs-8">
                                        Final booking calculation
                                    </div>

                                </div>

                            </div>


                            <div class="appointment-total-row">

                                <span class="text-muted">
                                    Subtotal
                                </span>

                                <span class="fw-semibold text-gray-800">

                                    LKR
                                    {{ number_format((float) $appointment->subtotal, 2) }}

                                </span>

                            </div>


                            <div class="appointment-total-row">

                                <span class="text-muted">
                                    Appointment Discount
                                </span>

                                <span class="fw-semibold
                                             text-success">

                                    - LKR
                                    {{ number_format((float) $appointment->discount_amount, 2) }}

                                </span>

                            </div>


                            <div class="appointment-total-row">

                                <span class="text-muted">
                                    Tax
                                </span>

                                <span class="fw-semibold text-gray-800">

                                    LKR
                                    {{ number_format((float) $appointment->tax_amount, 2) }}

                                </span>

                            </div>


                            <div
                                class="appointment-total-row
                                        appointment-grand-total">

                                <span
                                    class="fw-bolder
                                             text-gray-900 fs-5">
                                    Total
                                </span>

                                <span class="fw-bolder
                                             text-primary fs-4">

                                    LKR
                                    {{ number_format((float) $appointment->total_amount, 2) }}

                                </span>

                            </div>

                        </div>

                    </div>

                </div>



                {{-- ====================================================
                    STATUS HISTORY
                ==================================================== --}}

                <div class="card appointment-section-card
                            border-0 shadow-sm">

                    <div class="card-header border-0 pt-8 pb-2">

                        <div class="card-title">

                            <div class="d-flex align-items-center gap-4">

                                <div class="appointment-section-icon">

                                    <i
                                        class="bi bi-clock-history
                                              text-primary fs-3"></i>

                                </div>

                                <div>

                                    <h3
                                        class="fw-bolder
                                               text-gray-900 mb-1">
                                        Status History
                                    </h3>

                                    <div class="text-muted fs-7">
                                        Full appointment workflow audit trail.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-6">

                        <div class="status-timeline">

                            @forelse ($appointment->statusHistory
                                    ->sortByDesc('created_at')
                                as $history)
                                @php
                                    $historyStatusClass = match ($history->to_status) {
                                        'pending' => 'warning',

                                        'confirmed' => 'primary',

                                        'checked_in' => 'info',

                                        'in_progress' => 'success',

                                        'completed' => 'success',

                                        'cancelled' => 'danger',

                                        'no_show' => 'dark',

                                        default => 'secondary',
                                    };

                                    $historyIcon = match ($history->to_status) {
                                        'pending' => 'bi-clock',

                                        'confirmed' => 'bi-check-circle',

                                        'checked_in' => 'bi-box-arrow-in-right',

                                        'in_progress' => 'bi-scissors',

                                        'completed' => 'bi-check2-all',

                                        'cancelled' => 'bi-x-circle',

                                        'no_show' => 'bi-person-x',

                                        default => 'bi-circle',
                                    };
                                @endphp


                                <div class="status-history-item">

                                    <div
                                        class="timeline-marker
                                               bg-light-{{ $historyStatusClass }}
                                               text-{{ $historyStatusClass }}">

                                        <i class="bi {{ $historyIcon }}"></i>

                                    </div>


                                    <div class="timeline-content">

                                        <div
                                            class="d-flex
                                                    justify-content-between
                                                    flex-wrap gap-2">

                                            <div
                                                class="fw-bold
                                                        text-gray-900">

                                                @if ($history->from_status)
                                                    {{ str($history->from_status)->replace('_', ' ')->headline() }}

                                                    <i
                                                        class="bi
                                                              bi-arrow-right
                                                              mx-2 text-muted"></i>
                                                @endif

                                                {{ str($history->to_status)->replace('_', ' ')->headline() }}

                                            </div>


                                            <span
                                                class="badge
                                                       badge-light-{{ $historyStatusClass }}">

                                                {{ str($history->to_status)->replace('_', ' ')->headline() }}

                                            </span>

                                        </div>


                                        <div class="text-muted fs-8 mt-2">

                                            <i class="bi bi-calendar3 me-1"></i>

                                            {{ $history->created_at?->format('M d, Y h:i A') }}

                                            @if ($history->changedBy)
                                                <span class="mx-1">
                                                    •
                                                </span>

                                                <i class="bi bi-person me-1"></i>

                                                {{ $history->changedBy->name }}
                                            @endif

                                        </div>


                                        @if ($history->notes)
                                            <div
                                                class="appointment-note-box
                                                        mt-3 text-gray-700">

                                                {{ $history->notes }}

                                            </div>
                                        @endif

                                    </div>

                                </div>


                            @empty

                                <div class="text-center py-10">

                                    <div
                                        class="symbol symbol-70px
                                                mx-auto mb-5">

                                        <span
                                            class="symbol-label
                                                     bg-light-primary
                                                     rounded-circle">

                                            <i
                                                class="bi bi-clock-history
                                                      text-primary fs-2"></i>

                                        </span>

                                    </div>

                                    <div
                                        class="fw-bold
                                                text-gray-900 mb-1">
                                        No status history yet
                                    </div>

                                    <div class="text-muted fs-7">
                                        Appointment status changes will appear
                                        here.
                                    </div>

                                </div>
                            @endforelse

                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================
                RIGHT SIDEBAR
            ======================================================== --}}

            <div class="col-xl-4">

                <div class="appointment-profile-sticky position-sticky" style="top: 100px;">

                    {{-- ====================================================
                        BOOKING SUMMARY
                    ==================================================== --}}

                    <div class="card booking-summary-card
                                border-0 shadow-sm mb-8">

                        <div class="booking-summary-header
                                    px-7 pt-8 pb-6">

                            <div class="d-flex align-items-center gap-4">

                                <div class="customer-profile-avatar">

                                    {{ $customerInitials ?: 'CU' }}

                                </div>


                                <div class="min-w-0">

                                    <div
                                        class="text-muted fs-8
                                                fw-semibold
                                                text-uppercase mb-1">

                                        Customer

                                    </div>


                                    @if ($appointment->customer)
                                        <a href="{{ route('customer-management.customers.show', $appointment->customer) }}"
                                            class="fw-bolder text-gray-900
                                                   text-hover-primary fs-5">

                                            {{ $customerName }}

                                        </a>
                                    @else
                                        <div
                                            class="fw-bolder
                                                    text-gray-900 fs-5">

                                            {{ $customerName }}

                                        </div>
                                    @endif


                                    @if ($appointment->customer?->phone)
                                        <div class="text-muted fs-8 mt-1">

                                            <i
                                                class="bi bi-telephone
                                                      me-1"></i>

                                            {{ $appointment->customer->phone }}

                                        </div>
                                    @endif

                                </div>

                            </div>

                        </div>


                        <div class="card-body pt-2">

                            {{-- Branch --}}
                            <div class="summary-row">

                                <div class="summary-row-icon">

                                    <i class="bi bi-geo-alt"></i>

                                </div>

                                <div>

                                    <div class="text-muted fs-8 mb-1">
                                        Branch
                                    </div>

                                    <div class="fw-bold text-gray-900">
                                        {{ $appointment->branch?->name ?? '-' }}
                                    </div>

                                </div>

                            </div>


                            {{-- Source --}}
                            <div class="summary-row">

                                <div class="summary-row-icon">

                                    <i class="bi bi-bookmark"></i>

                                </div>

                                <div>

                                    <div class="text-muted fs-8 mb-1">
                                        Booking Source
                                    </div>

                                    <span class="badge badge-light-primary">
                                        {{ $appointment->booking_source_label }}
                                    </span>

                                </div>

                            </div>


                            {{-- Staff --}}
                            <div class="summary-row">

                                <div class="summary-row-icon">

                                    <i class="bi bi-people"></i>

                                </div>

                                <div class="min-w-0">

                                    <div class="text-muted fs-8 mb-1">
                                        Assigned Staff
                                    </div>

                                    <div class="fw-bold text-gray-900">

                                        {{ $staffNames->isNotEmpty() ? $staffNames->implode(', ') : 'Unassigned' }}

                                    </div>

                                </div>

                            </div>


                            {{-- Schedule --}}
                            <div class="summary-row">

                                <div class="summary-row-icon">

                                    <i class="bi bi-calendar-event"></i>

                                </div>

                                <div>

                                    <div class="text-muted fs-8 mb-1">
                                        Schedule
                                    </div>

                                    <div class="fw-bold text-gray-900">

                                        {{ $appointment->starts_at?->format('M d, Y') }}

                                    </div>

                                    <div class="text-muted fs-8 mt-1">

                                        {{ $appointment->starts_at?->format('h:i A') }}

                                        @if ($appointment->ends_at)
                                            –
                                            {{ $appointment->ends_at->format('h:i A') }}
                                        @endif

                                    </div>

                                </div>

                            </div>



                            {{-- Customer Notes --}}
                            @if ($appointment->customer_notes)
                                <div class="mt-6">

                                    <div
                                        class="d-flex
                                                align-items-center gap-2
                                                text-muted fs-8
                                                fw-semibold
                                                text-uppercase mb-3">

                                        <i class="bi bi-chat-left-text"></i>

                                        Customer Notes

                                    </div>

                                    <div
                                        class="appointment-note-box
                                                text-gray-800">

                                        {{ $appointment->customer_notes }}

                                    </div>

                                </div>
                            @endif



                            {{-- Internal Notes --}}
                            @can('viewInternalNotes', $appointment)

                                @if ($appointment->internal_notes)
                                    <div class="mt-6">

                                        <div
                                            class="d-flex
                                                    align-items-center gap-2
                                                    text-warning fs-8
                                                    fw-semibold
                                                    text-uppercase mb-3">

                                            <i class="bi bi-lock-fill"></i>

                                            Internal Notes

                                        </div>

                                        <div
                                            class="internal-note-box
                                                    text-gray-800">

                                            {{ $appointment->internal_notes }}

                                        </div>

                                    </div>
                                @endif

                            @endcan

                        </div>

                    </div>



                    {{-- ====================================================
                        STATUS WORKFLOW
                    ==================================================== --}}

                    <div class="card status-action-card
                                border-0 shadow-sm">

                        <div class="card-header border-0 pt-8 pb-2">

                            <div class="card-title">

                                <div>

                                    <div
                                        class="d-flex
                                                align-items-center gap-3 mb-1">

                                        <div class="symbol symbol-40px">

                                            <span
                                                class="symbol-label
                                                       bg-light-primary
                                                       rounded-3">

                                                <i
                                                    class="bi
                                                          bi-arrow-repeat
                                                          text-primary"></i>

                                            </span>

                                        </div>

                                        <h3
                                            class="fw-bolder
                                                   text-gray-900 mb-0">
                                            Appointment Workflow
                                        </h3>

                                    </div>

                                    <div class="text-muted fs-8 mt-2">
                                        Move this booking through its operational
                                        lifecycle.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="card-body pt-5">

                            <div class="workflow-step">

                                <span
                                    class="workflow-dot
                                           bg-{{ $statusClass }}"></span>

                                <div>

                                    <div class="text-muted fs-8">
                                        Current Status
                                    </div>

                                    <div class="fw-bold text-gray-900">
                                        {{ $appointment->status_label }}
                                    </div>

                                </div>

                            </div>


                            <div class="d-grid gap-3">


                                {{-- Confirm --}}
                                @can('confirm', $appointment)

                                    @if ($appointment->status === \App\Models\Appointment::STATUS_PENDING)
                                        <form method="POST"
                                            action="{{ route('appointment-management.appointments.confirm', $appointment) }}">

                                            @csrf

                                            <button
                                                class="btn btn-primary
                                                       status-action-btn w-100"
                                                type="submit">

                                                <i
                                                    class="bi
                                                          bi-check-circle
                                                          me-2"></i>

                                                Confirm Appointment

                                            </button>

                                        </form>
                                    @endif

                                @endcan



                                {{-- Check In --}}
                                @can('checkIn', $appointment)

                                    @if ($appointment->status === \App\Models\Appointment::STATUS_CONFIRMED)
                                        <form method="POST"
                                            action="{{ route('appointment-management.appointments.check-in', $appointment) }}">

                                            @csrf

                                            <button
                                                class="btn btn-info
                                                       status-action-btn w-100"
                                                type="submit">

                                                <i
                                                    class="bi
                                                          bi-box-arrow-in-right
                                                          me-2"></i>

                                                Check In Customer

                                            </button>

                                        </form>
                                    @endif

                                @endcan



                                {{-- Start --}}
                                @can('start', $appointment)

                                    @if ($appointment->status === \App\Models\Appointment::STATUS_CHECKED_IN)
                                        <form method="POST"
                                            action="{{ route('appointment-management.appointments.start', $appointment) }}">

                                            @csrf

                                            <button
                                                class="btn btn-success
                                                       status-action-btn w-100"
                                                type="submit">

                                                <i
                                                    class="bi
                                                          bi-play-circle
                                                          me-2"></i>

                                                Start Service

                                            </button>

                                        </form>
                                    @endif

                                @endcan



                                {{-- Complete --}}
                                @can('complete', $appointment)

                                    @if ($appointment->status === \App\Models\Appointment::STATUS_IN_PROGRESS)
                                        <form method="POST"
                                            action="{{ route('appointment-management.appointments.complete', $appointment) }}">

                                            @csrf

                                            <button
                                                class="btn btn-success
                                                       status-action-btn w-100"
                                                type="submit">

                                                <i
                                                    class="bi
                                                          bi-check2-all
                                                          me-2"></i>

                                                Complete Appointment

                                            </button>

                                        </form>
                                    @endif

                                @endcan



                                {{-- No Show --}}
                                @can('noShow', $appointment)

                                    @if ($appointment->status === \App\Models\Appointment::STATUS_CONFIRMED)
                                        <form method="POST"
                                            action="{{ route('appointment-management.appointments.no-show', $appointment) }}">

                                            @csrf

                                            <button
                                                class="btn btn-dark
                                                       status-action-btn w-100"
                                                type="submit">

                                                <i
                                                    class="bi
                                                          bi-person-x
                                                          me-2"></i>

                                                Mark as No-Show

                                            </button>

                                        </form>
                                    @endif

                                @endcan



                                {{-- Cancellation --}}
                                @can('cancel', $appointment)

                                    @if (in_array($appointment->status, \App\Models\Appointment::ACTIVE_STATUSES, true))
                                        <div
                                            class="separator
                                                    separator-dashed my-3">
                                        </div>


                                        <form method="POST"
                                            action="{{ route('appointment-management.appointments.cancel', $appointment) }}"
                                            class="cancellation-box">

                                            @csrf


                                            <div
                                                class="d-flex
                                                        align-items-center
                                                        gap-2 mb-3">

                                                <i
                                                    class="bi
                                                          bi-exclamation-triangle
                                                          text-danger"></i>

                                                <div
                                                    class="fw-bold
                                                            text-gray-900">
                                                    Cancel Appointment
                                                </div>

                                            </div>


                                            <textarea name="cancellation_reason"
                                                class="form-control
                                                       form-control-solid
                                                       mb-3"
                                                rows="3" placeholder="Enter cancellation reason..."></textarea>


                                            <button
                                                class="btn btn-light-danger
                                                       status-action-btn w-100"
                                                type="submit">

                                                <i
                                                    class="bi
                                                          bi-x-circle
                                                          me-2"></i>

                                                Cancel Appointment

                                            </button>

                                        </form>
                                    @endif

                                @endcan

                            </div>



                            {{-- Terminal status --}}
                            @if (in_array(
                                    $appointment->status,
                                    [
                                        \App\Models\Appointment::STATUS_COMPLETED,
                                        \App\Models\Appointment::STATUS_CANCELLED,
                                        \App\Models\Appointment::STATUS_NO_SHOW,
                                    ],
                                    true))
                                <div class="text-center py-5">

                                    <div
                                        class="symbol symbol-55px
                                                mx-auto mb-4">

                                        <span
                                            class="symbol-label
                                                   bg-light-{{ $statusClass }}
                                                   rounded-circle">

                                            <i
                                                class="bi {{ $statusIcon }}
                                                text-{{ $statusClass }}
                                                fs-3"></i>

                                        </span>

                                    </div>

                                    <div
                                        class="fw-bold
                                                text-gray-900 mb-1">

                                        {{ $appointment->status_label }}

                                    </div>

                                    <div class="text-muted fs-8">
                                        This appointment has reached a terminal
                                        workflow status.
                                    </div>

                                </div>
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>
