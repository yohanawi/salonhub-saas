<x-default-layout>

    @section('title')
        Appointment Calendar
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('appointment-management.appointments.calendar') }}
    @endsection


    @push('styles')
        <style>
            .calendar-workspace {
                --calendar-primary: #7239ea;
                --calendar-primary-soft: #f5f2ff;
                --calendar-border: #edf0f5;
                --calendar-muted: #a1a5b7;
            }

            /* ------------------------------------------------------------
                         | Hero
                         * ------------------------------------------------------------ */

            .calendar-hero {
                position: relative;
                overflow: hidden;
                border: 1px solid var(--calendar-border) !important;
                background:
                    radial-gradient(circle at 92% 10%,
                        rgba(114, 57, 234, .14),
                        transparent 28%),
                    radial-gradient(circle at 5% 100%,
                        rgba(0, 158, 247, .08),
                        transparent 30%),
                    linear-gradient(135deg,
                        #ffffff 0%,
                        #fafaff 100%);
            }

            .calendar-hero::before {
                content: '';
                position: absolute;
                width: 220px;
                height: 220px;
                border-radius: 50%;
                right: -90px;
                top: -120px;
                border: 35px solid rgba(114, 57, 234, .035);
            }

            .calendar-hero-icon {
                width: 72px;
                height: 72px;
                min-width: 72px;
                border-radius: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg,
                        #f2edff,
                        #eaf5ff);
                box-shadow: 0 14px 35px rgba(114, 57, 234, .12);
            }

            .calendar-date-chip {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 12px;
                border-radius: 10px;
                background: rgba(114, 57, 234, .08);
                color: var(--calendar-primary);
                font-size: .78rem;
                font-weight: 700;
            }

            /* ------------------------------------------------------------
                         | Summary
                         * ------------------------------------------------------------ */

            .calendar-summary-item {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .calendar-summary-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* ------------------------------------------------------------
                         | Filter
                         * ------------------------------------------------------------ */

            .calendar-filter-card {
                border: 1px solid var(--calendar-border) !important;
                background: linear-gradient(180deg, #ffffff, #fcfcff);
            }

            .calendar-filter-label {
                font-size: .74rem;
                font-weight: 700;
                letter-spacing: .045em;
                text-transform: uppercase;
                color: #7e8299;
            }

            .calendar-filter-card .form-control-solid,
            .calendar-filter-card .form-select-solid {
                min-height: 48px;
                border-radius: 12px;
            }

            /* ------------------------------------------------------------
                         | Toolbar
                         * ------------------------------------------------------------ */

            .calendar-board-card {
                border: 1px solid var(--calendar-border) !important;
            }

            .calendar-board-icon {
                width: 46px;
                height: 46px;
                min-width: 46px;
                border-radius: 14px;
                background: var(--calendar-primary-soft);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* ------------------------------------------------------------
                         | Staff Board
                         * ------------------------------------------------------------ */

            .appointment-calendar-scroll {
                overflow-x: auto;
                padding-bottom: 10px;
            }

            .appointment-calendar-grid {
                display: grid;
                gap: 18px;
                min-width: max-content;
                align-items: start;
            }

            .calendar-staff-column {
                width: 315px;
                min-width: 315px;
                overflow: hidden;
                border: 1px solid var(--calendar-border);
                border-radius: 18px;
                background: #fbfcfe;
                transition:
                    box-shadow .2s ease,
                    border-color .2s ease;
            }

            .calendar-staff-column:hover {
                border-color: rgba(114, 57, 234, .18);
                box-shadow: 0 12px 35px rgba(31, 41, 55, .05);
            }

            .calendar-staff-header {
                position: relative;
                padding: 20px;
                border-bottom: 1px solid var(--calendar-border);
                background:
                    radial-gradient(circle at 100% 0%,
                        rgba(114, 57, 234, .08),
                        transparent 36%),
                    #ffffff;
            }

            .staff-avatar-calendar {
                width: 48px;
                height: 48px;
                min-width: 48px;
                border-radius: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg,
                        #f0ebff,
                        #e7f5ff);
                color: var(--calendar-primary);
                font-weight: 800;
                font-size: .85rem;
            }

            .staff-booking-count {
                display: inline-flex;
                min-width: 27px;
                height: 27px;
                align-items: center;
                justify-content: center;
                padding: 0 8px;
                border-radius: 9px;
                background: #f4f5f8;
                color: #5e6278;
                font-size: .7rem;
                font-weight: 800;
            }

            .calendar-staff-body {
                min-height: 360px;
                padding: 14px;
            }

            /* ------------------------------------------------------------
                         | Appointment Cards
                         * ------------------------------------------------------------ */

            .calendar-appointment {
                position: relative;
                display: block;
                padding: 15px;
                overflow: hidden;
                border: 1px solid #edf0f5;
                border-radius: 14px;
                background: #ffffff;
                color: inherit;
                text-decoration: none !important;
                transition:
                    transform .18s ease,
                    border-color .18s ease,
                    box-shadow .18s ease;
            }

            .calendar-appointment::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: #b5b5c3;
            }

            .calendar-appointment:hover {
                transform: translateY(-2px);
                border-color: rgba(114, 57, 234, .22);
                box-shadow: 0 10px 24px rgba(31, 41, 55, .07);
            }

            .calendar-appointment.status-pending::before {
                background: #ffc700;
            }

            .calendar-appointment.status-confirmed::before {
                background: #7239ea;
            }

            .calendar-appointment.status-checked_in::before {
                background: #009ef7;
            }

            .calendar-appointment.status-in_progress::before {
                background: #50cd89;
            }

            .calendar-appointment.status-completed::before {
                background: #50cd89;
            }

            .calendar-appointment.status-cancelled::before {
                background: #f1416c;
            }

            .calendar-appointment.status-no_show::before {
                background: #3f4254;
            }

            .appointment-time {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: .78rem;
                font-weight: 800;
                color: #181c32;
            }

            .appointment-customer-avatar {
                width: 36px;
                height: 36px;
                min-width: 36px;
                border-radius: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f5f8fa;
                color: #5e6278;
                font-size: .7rem;
                font-weight: 800;
            }

            .appointment-service-tag {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 8px;
                margin: 2px 2px 2px 0;
                border-radius: 8px;
                background: #f8f9fc;
                color: #5e6278;
                font-size: .7rem;
                font-weight: 600;
            }

            .appointment-meta {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 11px;
                padding-top: 10px;
                border-top: 1px dashed #edf0f5;
            }

            /* ------------------------------------------------------------
                         | Available State
                         * ------------------------------------------------------------ */

            .staff-available-state {
                min-height: 320px;
                border: 1px dashed #e5e8ef;
                border-radius: 14px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                background:
                    repeating-linear-gradient(-45deg,
                        #fcfcfd,
                        #fcfcfd 8px,
                        #fafbfc 8px,
                        #fafbfc 16px);
            }

            .available-icon {
                width: 54px;
                height: 54px;
                border-radius: 17px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #edfdf5;
            }

            /* ------------------------------------------------------------
                         | Empty Screen
                         * ------------------------------------------------------------ */

            .calendar-empty-icon {
                width: 110px;
                height: 110px;
                margin-inline: auto;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 32px;
                background: linear-gradient(135deg,
                        #f2eeff,
                        #ebf7ff);
                box-shadow: 0 16px 38px rgba(114, 57, 234, .08);
            }

            @media (max-width: 991.98px) {
                .calendar-hero-icon {
                    width: 60px;
                    height: 60px;
                    min-width: 60px;
                    border-radius: 18px;
                }

                .calendar-staff-column {
                    width: 290px;
                    min-width: 290px;
                }
            }

            @media (max-width: 575.98px) {
                .appointment-calendar-grid {
                    display: flex;
                    flex-direction: column;
                    min-width: 100%;
                }

                .calendar-staff-column {
                    width: 100%;
                    min-width: 100%;
                }
            }
        </style>
    @endpush


    @php
        /*
        |--------------------------------------------------------------------------
        | Calendar collections
        |--------------------------------------------------------------------------
        */

        $staffColumns = $staff
            ->filter(function ($member) use ($appointments) {
                return !request('staff_id') ||
                    (string) request('staff_id') === (string) $member->id ||
                    $appointments->contains(
                        fn($appointment) => $appointment->appointmentServices->contains('staff_id', $member->id),
                    );
            })
            ->values();

        $confirmedCount = $appointments->where('status', 'confirmed')->count();

        $activeCount = $appointments->whereIn('status', ['checked_in', 'in_progress'])->count();

        $completedCount = $appointments->where('status', 'completed')->count();
    @endphp


    <div id="kt_app_content_container" class="calendar-workspace">

        {{-- ============================================================
            HERO
        ============================================================ --}}
        <div class="card calendar-hero border-0 shadow-sm mb-8">

            <div class="card-body p-8 p-lg-10 position-relative">

                <div
                    class="d-flex flex-column flex-xl-row
                            justify-content-between
                            align-items-xl-center gap-7">

                    <div class="d-flex align-items-center gap-5">

                        <div class="calendar-hero-icon">

                            <i
                                class="bi bi-calendar2-week-fill
                                      text-primary fs-1"></i>

                        </div>


                        <div>

                            <div
                                class="d-flex flex-wrap
                                        align-items-center gap-3 mb-2">

                                <h1
                                    class="fs-2hx fw-bolder
                                           text-gray-900 mb-0">
                                    Appointment Calendar
                                </h1>

                                <span
                                    class="badge badge-light-primary
                                             px-3 py-2">

                                    {{ $appointments->count() }}
                                    {{ Str::plural('booking', $appointments->count()) }}

                                </span>

                            </div>


                            <div class="calendar-date-chip">

                                <i class="bi bi-calendar-event"></i>

                                {{ $date->format('l, F d, Y') }}

                            </div>


                            <div class="text-muted fs-7 mt-3">
                                Day / Staff View.
                                Track each staff member's daily schedule,
                                service assignments and appointment progress.
                            </div>

                        </div>

                    </div>


                    <div class="d-flex flex-column flex-sm-row gap-3">

                        <a href="{{ route('appointment-management.appointments.index', request()->query()) }}"
                            class="btn btn-light-primary px-6">
                            <i class="bi bi-list-ul me-2"></i>
                            List View
                        </a>


                        @can('create', \App\Models\Appointment::class)
                            <a href="{{ route('appointment-management.appointments.create') }}"
                                class="btn btn-primary px-6">
                                <i class="bi bi-plus-lg me-2"></i>
                                New Appointment
                            </a>
                        @endcan

                    </div>

                </div>


                {{-- Summary --}}
                <div class="d-flex flex-wrap gap-8
                            mt-8 pt-6 border-top position-relative">

                    <div class="calendar-summary-item">

                        <div class="calendar-summary-icon bg-light-primary">
                            <i class="bi bi-calendar-check
                                      text-primary"></i>
                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Appointments
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $appointments->count() }}
                            </div>

                        </div>

                    </div>


                    <div class="calendar-summary-item">

                        <div class="calendar-summary-icon bg-light-info">
                            <i class="bi bi-people text-info"></i>
                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Staff Scheduled
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $staffColumns->count() }}
                            </div>

                        </div>

                    </div>


                    <div class="calendar-summary-item">

                        <div class="calendar-summary-icon bg-light-primary">
                            <i class="bi bi-check-circle
                                      text-primary"></i>
                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Confirmed
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $confirmedCount }}
                            </div>

                        </div>

                    </div>


                    <div class="calendar-summary-item">

                        <div class="calendar-summary-icon bg-light-warning">
                            <i class="bi bi-scissors
                                      text-warning"></i>
                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                In Service
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $activeCount }}
                            </div>

                        </div>

                    </div>


                    <div class="calendar-summary-item">

                        <div class="calendar-summary-icon bg-light-success">
                            <i class="bi bi-check2-all
                                      text-success"></i>
                        </div>

                        <div>

                            <div class="text-muted fs-8">
                                Completed
                            </div>

                            <div class="fw-bolder text-gray-900">
                                {{ $completedCount }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
            FILTERS
        ============================================================ --}}
        <div class="card calendar-filter-card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-7 pb-2">

                <div class="card-title">

                    <div class="d-flex align-items-center gap-4">

                        <div class="symbol symbol-45px">

                            <span
                                class="symbol-label
                                         bg-light-primary rounded-3">

                                <i
                                    class="bi bi-sliders2
                                          text-primary fs-3"></i>

                            </span>

                        </div>


                        <div>

                            <h3 class="fw-bold text-gray-900 mb-1">
                                Calendar Filters
                            </h3>

                            <div class="text-muted fs-7">
                                Change the working day, salon or branch.
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-5">

                <form method="GET" action="{{ route('appointment-management.appointments.calendar') }}">

                    <div class="row g-5 align-items-end">


                        {{-- Tenant --}}
                        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())

                            <div class="col-lg-6 col-xl-3">

                                <label class="form-label calendar-filter-label">
                                    Salon
                                </label>

                                <select name="tenant_id" class="form-select form-select-solid" data-control="select2"
                                    data-hide-search="true" data-placeholder="All salons">

                                    <option value="">
                                        All salons
                                    </option>

                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        @endif


                        {{-- Date --}}
                        <div class="col-lg-6 col-xl-3">

                            <label class="form-label calendar-filter-label">
                                Working Date
                            </label>

                            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                                class="form-control form-control-solid">

                        </div>


                        {{-- Branch --}}
                        <div class="col-lg-6 col-xl-3">

                            <label class="form-label calendar-filter-label">
                                Branch
                            </label>

                            <select name="branch_id" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true" data-placeholder="All branches">

                                <option value="">
                                    All branches
                                </option>

                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Submit --}}
                        <div class="col-lg-6 col-xl-3">

                            <button type="submit" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-calendar2-check me-2"></i>
                                Load Schedule
                            </button>

                        </div>

                    </div>


                    <div
                        class="d-flex flex-wrap
                                justify-content-between
                                align-items-center gap-3
                                mt-6 pt-5 border-top">

                        <div class="text-muted fs-8">

                            <i class="bi bi-info-circle me-1"></i>

                            The board displays appointments assigned to
                            each eligible staff member.

                        </div>


                        @if (request()->hasAny(['tenant_id', 'branch_id']))
                            <a href="{{ route('appointment-management.appointments.calendar', ['date' => $date->format('Y-m-d')]) }}"
                                class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                Reset Filters
                            </a>
                        @endif

                    </div>

                </form>

            </div>

        </div>



        {{-- ============================================================
            STAFF CALENDAR BOARD
        ============================================================ --}}
        <div class="card calendar-board-card border-0 shadow-sm">

            <div class="card-header border-0 py-7">

                <div class="card-title">

                    <div class="d-flex align-items-center gap-4">

                        <div class="calendar-board-icon">
                            <i class="bi bi-columns-gap
                                      text-primary fs-3"></i>
                        </div>

                        <div>

                            <div
                                class="d-flex align-items-center
                                        flex-wrap gap-3 mb-1">

                                <h3 class="fw-bolder
                                           text-gray-900 mb-0">
                                    Staff Schedule
                                </h3>

                                <span class="badge badge-light">
                                    {{ $staffColumns->count() }}
                                    staff
                                </span>

                            </div>

                            <div class="text-muted fs-7">

                                {{ $appointments->count() }}
                                {{ Str::plural('appointment', $appointments->count()) }}
                                scheduled for
                                {{ $date->format('M d, Y') }}.

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-toolbar d-none d-md-flex">

                    <div class="d-flex gap-4 text-muted fs-8">

                        <span>
                            <span class="bullet bullet-dot bg-warning me-1"></span>
                            Pending
                        </span>

                        <span>
                            <span class="bullet bullet-dot bg-primary me-1"></span>
                            Confirmed
                        </span>

                        <span>
                            <span class="bullet bullet-dot bg-success me-1"></span>
                            Active
                        </span>

                    </div>

                </div>

            </div>


            <div class="card-body pt-0">

                @if ($staffColumns->isNotEmpty())

                    <div class="appointment-calendar-scroll">

                        <div class="appointment-calendar-grid"
                            style="
                                grid-template-columns:
                                    repeat(
                                        {{ max(1, $staffColumns->count()) }},
                                        315px
                                    );
                            ">

                            @foreach ($staffColumns as $member)
                                @php
                                    $staffAppointments = $appointments
                                        ->filter(
                                            fn($appointment) => $appointment->appointmentServices->contains(
                                                'staff_id',
                                                $member->id,
                                            ),
                                        )
                                        ->sortBy('starts_at')
                                        ->values();

                                    $memberInitials = collect(preg_split('/\s+/', trim($member->full_name)))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn($part) => strtoupper(mb_substr($part, 0, 1)))
                                        ->implode('');
                                @endphp


                                <div class="calendar-staff-column">

                                    {{-- Staff Header --}}
                                    <div class="calendar-staff-header">

                                        <div
                                            class="d-flex
                                                    justify-content-between
                                                    align-items-center gap-3">

                                            <div
                                                class="d-flex
                                                        align-items-center gap-3">

                                                <div class="staff-avatar-calendar">
                                                    {{ $memberInitials ?: 'ST' }}
                                                </div>


                                                <div class="min-w-0">

                                                    <div
                                                        class="fw-bolder
                                                                text-gray-900
                                                                text-truncate">
                                                        {{ $member->full_name }}
                                                    </div>

                                                    <div
                                                        class="text-muted
                                                                fs-8
                                                                text-truncate">
                                                        {{ $member->job_title ?: 'Bookable Staff' }}
                                                    </div>

                                                </div>

                                            </div>


                                            <span class="staff-booking-count">
                                                {{ $staffAppointments->count() }}
                                            </span>

                                        </div>


                                        <div
                                            class="d-flex
                                                    align-items-center gap-2
                                                    mt-4 text-muted fs-8">

                                            <i class="bi bi-calendar-check"></i>

                                            {{ $staffAppointments->count() }}
                                            {{ Str::plural('booking', $staffAppointments->count()) }}

                                        </div>

                                    </div>


                                    {{-- Appointments --}}
                                    <div class="calendar-staff-body">

                                        <div
                                            class="d-flex
                                                    flex-column gap-3">

                                            @forelse ($staffAppointments
                                                as $appointment)
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

                                                        'confirmed' => 'bi-check-circle',

                                                        'checked_in' => 'bi-box-arrow-in-right',

                                                        'in_progress' => 'bi-scissors',

                                                        'completed' => 'bi-check2-all',

                                                        'cancelled' => 'bi-x-circle',

                                                        'no_show' => 'bi-person-x',

                                                        default => 'bi-circle',
                                                    };

                                                    $customerName =
                                                        $appointment->customer?->full_name ?: 'Walk-in Customer';

                                                    $customerInitials = collect(
                                                        preg_split('/\s+/', trim($customerName)),
                                                    )
                                                        ->filter()
                                                        ->take(2)
                                                        ->map(fn($part) => strtoupper(mb_substr($part, 0, 1)))
                                                        ->implode('');

                                                    $memberServices = $appointment->appointmentServices->where(
                                                        'staff_id',
                                                        $member->id,
                                                    );

                                                    $serviceNames = $memberServices->pluck('service_name')->filter();

                                                    if ($serviceNames->isEmpty()) {
                                                        $serviceNames = $memberServices
                                                            ->pluck('service.name')
                                                            ->filter();
                                                    }
                                                @endphp


                                                <a href="{{ route('appointment-management.appointments.show', $appointment) }}"
                                                    class="calendar-appointment
                                                           status-{{ $appointment->status }}">

                                                    {{-- Time / Status --}}
                                                    <div
                                                        class="d-flex
                                                                justify-content-between
                                                                align-items-start
                                                                gap-3">

                                                        <div class="appointment-time">

                                                            <i
                                                                class="bi bi-clock
                                                                      text-primary"></i>

                                                            {{ $appointment->starts_at?->format('h:i A') }}

                                                            @if ($appointment->ends_at)
                                                                <span class="text-muted">
                                                                    –
                                                                </span>

                                                                {{ $appointment->ends_at->format('h:i A') }}
                                                            @endif

                                                        </div>


                                                        <span
                                                            class="badge
                                                                   badge-light-{{ $statusClass }}
                                                                   fs-8">
                                                            <i class="bi {{ $statusIcon }} me-1"></i>

                                                            {{ $appointment->status_label }}
                                                        </span>

                                                    </div>


                                                    {{-- Customer --}}
                                                    <div
                                                        class="d-flex
                                                                align-items-center
                                                                gap-3 mt-4">

                                                        <div class="appointment-customer-avatar">

                                                            {{ $customerInitials ?: 'CU' }}

                                                        </div>


                                                        <div class="min-w-0">

                                                            <div
                                                                class="fw-bold
                                                                        text-gray-900
                                                                        text-truncate">

                                                                {{ $customerName }}

                                                            </div>


                                                            @if ($appointment->customer?->phone)
                                                                <div
                                                                    class="text-muted
                                                                            fs-8
                                                                            mt-1">

                                                                    <i
                                                                        class="bi
                                                                              bi-telephone
                                                                              me-1"></i>

                                                                    {{ $appointment->customer->phone }}

                                                                </div>
                                                            @endif

                                                        </div>

                                                    </div>


                                                    {{-- Services --}}
                                                    <div class="mt-4">

                                                        @if ($serviceNames->isNotEmpty())
                                                            @foreach ($serviceNames->take(2) as $serviceName)
                                                                <span class="appointment-service-tag">
                                                                    <i
                                                                        class="bi
                                                                               bi-stars
                                                                               text-primary"></i>

                                                                    {{ $serviceName }}
                                                                </span>
                                                            @endforeach


                                                            @if ($serviceNames->count() > 2)
                                                                <span class="appointment-service-tag">
                                                                    +{{ $serviceNames->count() - 2 }}
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span
                                                                class="text-muted
                                                                         fs-8">
                                                                No service details
                                                            </span>
                                                        @endif

                                                    </div>


                                                    {{-- Meta --}}
                                                    <div class="appointment-meta">

                                                        <span class="text-muted fs-8">

                                                            <i
                                                                class="bi
                                                                      bi-hash
                                                                      me-1"></i>

                                                            {{ $appointment->appointment_number }}

                                                        </span>


                                                        @if ($appointment->branch)
                                                            <span class="text-muted fs-8">

                                                                <i
                                                                    class="bi
                                                                          bi-geo-alt
                                                                          me-1"></i>

                                                                {{ $appointment->branch->name }}

                                                            </span>
                                                        @endif

                                                    </div>

                                                </a>


                                            @empty

                                                <div class="staff-available-state">

                                                    <div class="available-icon mb-4">

                                                        <i
                                                            class="bi
                                                                  bi-calendar2-check
                                                                  text-success
                                                                  fs-2"></i>

                                                    </div>

                                                    <div
                                                        class="fw-bold
                                                                text-gray-800
                                                                mb-1">
                                                        Available
                                                    </div>

                                                    <div
                                                        class="text-muted
                                                                fs-8
                                                                px-5">
                                                        No appointments are assigned
                                                        to this staff member for
                                                        this day.
                                                    </div>

                                                </div>
                                            @endforelse

                                        </div>

                                    </div>

                                </div>
                            @endforeach

                        </div>

                    </div>
                @else
                    {{-- ====================================================
                        NO STAFF
                    ==================================================== --}}
                    <div class="text-center py-15 px-5">

                        <div class="calendar-empty-icon mb-7">

                            <i class="bi bi-person-workspace text-primary" style="font-size: 3.2rem;"></i>

                        </div>


                        <h2 class="fw-bolder text-gray-900 mb-3">
                            No bookable staff found
                        </h2>


                        <div class="text-muted fs-6
                                    mw-550px mx-auto mb-7">

                            There are no active staff members available
                            for the selected calendar filters. Make sure
                            staff are assigned to the required branch
                            and services.

                        </div>


                        <a href="{{ route('appointment-management.appointments.calendar', ['date' => $date->format('Y-m-d')]) }}"
                            class="btn btn-light-primary">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Reset Calendar Filters
                        </a>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-default-layout>
