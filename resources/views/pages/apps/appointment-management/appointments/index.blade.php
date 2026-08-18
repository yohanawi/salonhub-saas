<x-default-layout>

    @section('title')
        Appointments
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('appointment-management.appointments.index') }}
    @endsection

    <style>
        .appointment-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(99, 102, 241, .18), transparent 30%),
                radial-gradient(circle at bottom left, rgba(14, 165, 233, .14), transparent 35%),
                linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
        }

        .appointment-hero::after {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            right: -90px;
            top: -100px;
            border-radius: 50%;
            background: rgba(99, 102, 241, .05);
        }

        .hero-icon {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            box-shadow: 0 12px 30px rgba(79, 70, 229, .12);
        }

        .appointments-filter-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        }

        .filter-label {
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #7e8299;
        }

        .appointment-table thead th {
            border-bottom: 1px solid #eff2f5 !important;
            white-space: nowrap;
        }

        .appointment-table tbody tr {
            transition: all .2s ease;
        }

        .appointment-table tbody tr:hover {
            background: #fafbff;
        }

        .appointment-number {
            transition: color .2s ease;
        }

        .appointment-avatar {
            width: 44px;
            height: 44px;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-weight: 700;
            font-size: .9rem;
            background: linear-gradient(135deg, #f1f5ff, #e8edff);
            color: #4f46e5;
        }

        .appointment-time-card {
            min-width: 58px;
            padding: 8px 10px;
            border-radius: 12px;
            background: #f8f9fc;
            text-align: center;
        }

        .appointment-time-card .day {
            font-weight: 800;
            font-size: 1rem;
            line-height: 1;
            color: #181c32;
        }

        .appointment-time-card .month {
            margin-top: 4px;
            font-size: .65rem;
            font-weight: 700;
            color: #7e8299;
            text-transform: uppercase;
        }

        .service-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 9px;
            margin: 2px 2px 2px 0;
            border-radius: 8px;
            background: #f5f8fa;
            color: #3f4254;
            font-size: .75rem;
            font-weight: 600;
        }

        .staff-avatar {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef6ff;
            color: #009ef7;
            font-size: .7rem;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 6px;
        }

        .booking-source {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 8px;
            background: #f8f9fa;
            color: #7e8299;
            font-size: .68rem;
            font-weight: 600;
        }

        .empty-appointment-icon {
            width: 110px;
            height: 110px;
            margin: auto;
            border-radius: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f1f5ff, #edf7ff);
            box-shadow: 0 15px 40px rgba(99, 102, 241, .08);
        }

        @media (max-width: 991.98px) {
            .appointment-hero .hero-icon {
                width: 60px;
                height: 60px;
                border-radius: 18px;
            }
        }
    </style>

    <div id="kt_app_content_container">

        {{-- ============================================================
            ALERTS
        ============================================================ --}}
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-7">
                <div class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-success bg-opacity-10 rounded-3">
                        <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    </span>
                </div>

                <div class="flex-grow-1">
                    <div class="fw-bold text-gray-900 mb-1">
                        Operation completed
                    </div>
                    <div class="text-gray-700">
                        {{ session('status') }}
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-icon btn-light-success" data-bs-dismiss="alert">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-7">
                <div class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-danger bg-opacity-10 rounded-3">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    </span>
                </div>

                <div class="flex-grow-1">
                    <div class="fw-bold text-gray-900 mb-1">
                        Something went wrong
                    </div>
                    <div class="text-gray-700">
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif


        {{-- ============================================================
            HERO / PAGE HEADER
        ============================================================ --}}
        <div class="card appointment-hero border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10 position-relative">

                <div
                    class="d-flex flex-column flex-xl-row
                            justify-content-between align-items-xl-center gap-7">

                    <div class="d-flex align-items-center gap-5">

                        <div class="hero-icon">
                            <i class="bi bi-calendar2-week-fill text-primary fs-1"></i>
                        </div>

                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">

                                <h1 class="fs-2hx fw-bolder text-gray-900 mb-0">
                                    Appointments
                                </h1>

                                <span class="badge badge-light-primary fs-7 px-3 py-2">
                                    {{ number_format($appointments->total()) }}
                                    {{ Str::plural('booking', $appointments->total()) }}
                                </span>

                            </div>

                            <div class="text-gray-600 fs-6 mw-650px">
                                Manage salon bookings, customer schedules,
                                assigned staff, services and appointment progress
                                from one workspace.
                            </div>
                        </div>

                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3">

                        <a href="{{ route('appointment-management.appointments.calendar', request()->query()) }}"
                            class="btn btn-light-primary px-6">
                            <i class="bi bi-calendar3 fs-4 me-2"></i>
                            Calendar View
                        </a>

                        @can('create', \App\Models\Appointment::class)
                            <a href="{{ route('appointment-management.appointments.create') }}"
                                class="btn btn-primary px-6">
                                <i class="bi bi-plus-lg fs-4 me-2"></i>
                                New Appointment
                            </a>
                        @endcan

                    </div>

                </div>

                {{-- Quick contextual information --}}
                <div class="d-flex flex-wrap gap-6 mt-8 pt-6 border-top">

                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-35px">
                            <span class="symbol-label bg-light-primary">
                                <i class="bi bi-collection text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-8">Total appointments</div>
                            <div class="fw-bold text-gray-900">
                                {{ number_format($appointments->total()) }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-35px">
                            <span class="symbol-label bg-light-info">
                                <i class="bi bi-building text-info"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-8">Available branches</div>
                            <div class="fw-bold text-gray-900">
                                {{ number_format($branches->count()) }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-35px">
                            <span class="symbol-label bg-light-success">
                                <i class="bi bi-people text-success"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-8">Available staff</div>
                            <div class="fw-bold text-gray-900">
                                {{ number_format($staff->count()) }}
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        {{-- ============================================================
            FILTERS
        ============================================================ --}}
        <div class="card appointments-filter-card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-7 pb-2">

                <div class="card-title">
                    <div class="d-flex align-items-center gap-4">

                        <div class="symbol symbol-45px">
                            <span class="symbol-label bg-light-primary rounded-3">
                                <i class="bi bi-sliders text-primary fs-3"></i>
                            </span>
                        </div>

                        <div>
                            <h3 class="fw-bold text-gray-900 mb-1">
                                Search & Filter
                            </h3>

                            <div class="text-muted fs-7">
                                Quickly find appointments using booking,
                                customer or scheduling information.
                            </div>
                        </div>

                    </div>
                </div>

            </div>


            <div class="card-body pt-5">

                <form method="GET" action="{{ route('appointment-management.appointments.index') }}">

                    <div class="row g-5">

                        {{-- Search --}}
                        <div class="col-12 col-xl-4">

                            <label class="form-label filter-label fw-bold">
                                Search appointment
                            </label>

                            <div class="position-relative">

                                <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>

                                <input type="search" name="search" value="{{ request('search') }}"
                                    class="form-control form-control-solid ps-11"
                                    placeholder="Appointment no, customer or phone...">

                            </div>

                        </div>


                        {{-- Tenant --}}
                        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())

                            <div class="col-md-6 col-xl-2">

                                <label class="form-label filter-label fw-bold">
                                    Salon
                                </label>

                                <select name="tenant_id" class="form-select form-select-solid" data-control="select2"
                                    data-placeholder="All salons">
                                    <option value="">All salons</option>

                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        @endif


                        {{-- Date --}}
                        <div class="col-md-6 col-xl-2">

                            <label class="form-label filter-label fw-bold">
                                Appointment Date
                            </label>

                            <input type="date" name="date" value="{{ request('date') }}"
                                class="form-control form-control-solid">

                        </div>


                        {{-- Branch --}}
                        <div class="col-md-6 col-xl-2">

                            <label class="form-label filter-label fw-bold">
                                Branch
                            </label>

                            <select name="branch_id" class="form-select form-select-solid" data-control="select2"
                                data-placeholder="All branches">
                                <option value="">All branches</option>

                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Staff --}}
                        <div class="col-md-6 col-xl-2">

                            <label class="form-label filter-label fw-bold">
                                Staff Member
                            </label>

                            <select name="staff_id" class="form-select form-select-solid" data-control="select2"
                                data-placeholder="All staff">
                                <option value="">All staff</option>

                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>
                                        {{ $member->full_name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Status --}}
                        <div class="col-md-6 col-xl-2">

                            <label class="form-label filter-label fw-bold">
                                Status
                            </label>

                            <select name="status" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All statuses</option>

                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>
                                        {{ str($status)->replace('_', ' ')->headline() }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- Filter Buttons --}}
                    <div
                        class="d-flex flex-column flex-sm-row
                                justify-content-between align-items-sm-center
                                gap-4 mt-7 pt-6 border-top">

                        <div class="text-muted fs-7">

                            @if (request()->hasAny(['search', 'tenant_id', 'date', 'branch_id', 'staff_id', 'status']))
                                <i class="bi bi-funnel-fill text-primary me-1"></i>
                                Filters are currently applied
                            @else
                                <i class="bi bi-info-circle me-1"></i>
                                Showing all available appointments
                            @endif

                        </div>


                        <div class="d-flex gap-3">

                            <a href="{{ route('appointment-management.appointments.index') }}" class="btn btn-light">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                Reset
                            </a>

                            <button type="submit" class="btn btn-primary px-7">
                                <i class="bi bi-search me-2"></i>
                                Search Appointments
                            </button>

                        </div>

                    </div>

                </form>

            </div>
        </div>


        {{-- ============================================================
            APPOINTMENT LIST
        ============================================================ --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 py-7">

                <div class="card-title">

                    <div>

                        <div class="d-flex align-items-center gap-3 mb-1">

                            <h3 class="fw-bold text-gray-900 mb-0">
                                Appointment List
                            </h3>

                            <span class="badge badge-light">
                                {{ number_format($appointments->total()) }}
                            </span>

                        </div>

                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-semibold">
                                {{ $appointments->firstItem() ?? 0 }}
                            </span>
                            –
                            <span class="fw-semibold">
                                {{ $appointments->lastItem() ?? 0 }}
                            </span>
                            of
                            <span class="fw-semibold">
                                {{ number_format($appointments->total()) }}
                            </span>
                            appointments
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-0">

                @if ($appointments->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table appointment-table align-middle gy-6">

                            <thead>

                                <tr
                                    class="text-start text-gray-500 fw-bold
                                           fs-8 text-uppercase">

                                    <th class="min-w-220px">
                                        Appointment
                                    </th>

                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-160px">
                                            Salon
                                        </th>
                                    @endif

                                    <th class="min-w-220px">
                                        Customer
                                    </th>

                                    <th class="min-w-240px">
                                        Services
                                    </th>

                                    <th class="min-w-180px">
                                        Staff
                                    </th>

                                    <th class="min-w-150px">
                                        Branch
                                    </th>

                                    <th class="min-w-140px">
                                        Status
                                    </th>

                                    <th class="text-end min-w-130px">
                                        Total
                                    </th>

                                    <th class="text-end min-w-80px">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($appointments as $appointment)
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

                                        $customerName = $appointment->customer?->full_name ?? 'Walk-in Customer';

                                        $customerInitials = collect(preg_split('/\s+/', trim($customerName)))
                                            ->filter()
                                            ->take(2)
                                            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                            ->implode('');

                                        $serviceNames = $appointment->appointmentServices
                                            ->pluck('service_name')
                                            ->filter();

                                        if ($serviceNames->isEmpty()) {
                                            $serviceNames = $appointment->appointmentServices
                                                ->pluck('service.name')
                                                ->filter();
                                        }

                                        $staffMembers = $appointment->appointmentServices
                                            ->pluck('staff')
                                            ->filter()
                                            ->unique('id');

                                    @endphp


                                    <tr>

                                        {{-- Appointment --}}
                                        <td>

                                            <div class="d-flex align-items-center gap-4">

                                                <div class="appointment-time-card">

                                                    <div class="day">
                                                        {{ $appointment->starts_at?->format('d') ?? '--' }}
                                                    </div>

                                                    <div class="month">
                                                        {{ $appointment->starts_at?->format('M') ?? '---' }}
                                                    </div>

                                                </div>


                                                <div>

                                                    <a href="{{ route('appointment-management.appointments.show', $appointment) }}"
                                                        class="appointment-number text-gray-900
                                                               text-hover-primary fw-bolder fs-6">
                                                        {{ $appointment->appointment_number }}
                                                    </a>


                                                    <div
                                                        class="d-flex align-items-center
                                                                flex-wrap gap-2 mt-2">

                                                        <span class="text-gray-600 fs-8">

                                                            <i class="bi bi-clock me-1 text-muted"></i>

                                                            {{ $appointment->starts_at?->format('h:i A') }}

                                                            @if ($appointment->ends_at)
                                                                –
                                                                {{ $appointment->ends_at->format('h:i A') }}
                                                            @endif

                                                        </span>

                                                    </div>


                                                    <div class="mt-2">

                                                        <span class="booking-source">

                                                            <i class="bi bi-bookmark"></i>

                                                            {{ $appointment->booking_source_label }}

                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>

                                                <div class="d-flex align-items-center gap-3">

                                                    <div class="symbol symbol-35px">

                                                        <span class="symbol-label bg-light-primary rounded-3">
                                                            <i class="bi bi-shop text-primary"></i>
                                                        </span>

                                                    </div>

                                                    <span class="fw-bold text-gray-800">
                                                        {{ $appointment->tenant?->name ?? '-' }}
                                                    </span>

                                                </div>

                                            </td>
                                        @endif


                                        {{-- Customer --}}
                                        <td>

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="appointment-avatar">
                                                    {{ $customerInitials ?: 'CU' }}
                                                </div>


                                                <div>

                                                    <div class="fw-bold text-gray-900">
                                                        {{ $customerName }}
                                                    </div>

                                                    @if ($appointment->customer?->phone)
                                                        <div class="text-muted fs-8 mt-1">

                                                            <i class="bi bi-telephone me-1"></i>

                                                            {{ $appointment->customer->phone }}

                                                        </div>
                                                    @else
                                                        <div class="text-muted fs-8 mt-1">
                                                            No phone number
                                                        </div>
                                                    @endif

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Services --}}
                                        <td>

                                            @if ($serviceNames->isNotEmpty())
                                                <div>

                                                    @foreach ($serviceNames->take(2) as $serviceName)
                                                        <span class="service-pill">
                                                            <i class="bi bi-stars text-primary"></i>
                                                            {{ $serviceName }}
                                                        </span>
                                                    @endforeach


                                                    @if ($serviceNames->count() > 2)
                                                        <span class="badge badge-light-primary ms-1">
                                                            +{{ $serviceNames->count() - 2 }} more
                                                        </span>
                                                    @endif

                                                </div>

                                                <div class="text-muted fs-8 mt-2">
                                                    {{ $serviceNames->count() }}
                                                    {{ Str::plural('service', $serviceNames->count()) }}
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    No services
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Staff --}}
                                        <td>

                                            @if ($staffMembers->isNotEmpty())
                                                <div class="d-flex align-items-center mb-2">

                                                    @foreach ($staffMembers->take(3) as $member)
                                                        @php
                                                            $memberInitials = collect(
                                                                preg_split('/\s+/', trim($member->full_name)),
                                                            )
                                                                ->filter()
                                                                ->take(2)
                                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                                ->implode('');
                                                        @endphp

                                                        <div class="staff-avatar border border-white me-n2"
                                                            title="{{ $member->full_name }}">
                                                            {{ $memberInitials }}
                                                        </div>
                                                    @endforeach

                                                </div>


                                                <div class="text-gray-800 fs-8">

                                                    {{ $staffMembers->pluck('full_name')->take(2)->implode(', ') }}

                                                    @if ($staffMembers->count() > 2)
                                                        +{{ $staffMembers->count() - 2 }}
                                                    @endif

                                                </div>
                                            @else
                                                <span class="badge badge-light">
                                                    Unassigned
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Branch --}}
                                        <td>

                                            @if ($appointment->branch)
                                                <div class="d-flex align-items-center gap-2">

                                                    <div class="symbol symbol-30px">

                                                        <span class="symbol-label bg-light-info">
                                                            <i class="bi bi-geo-alt-fill text-info fs-8"></i>
                                                        </span>

                                                    </div>

                                                    <span class="fw-semibold text-gray-800">
                                                        {{ $appointment->branch->name }}
                                                    </span>

                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    -
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            <span
                                                class="badge badge-light-{{ $statusClass }}
                                                       px-3 py-2">
                                                <i class="bi {{ $statusIcon }} me-1"></i>

                                                {{ $appointment->status_label }}
                                            </span>

                                        </td>


                                        {{-- Amount --}}
                                        <td class="text-end">

                                            <div class="fw-bolder text-gray-900 fs-6">
                                                LKR
                                                {{ number_format((float) $appointment->total_amount, 2) }}
                                            </div>

                                            <div class="text-muted fs-8 mt-1">
                                                Appointment total
                                            </div>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <div class="dropdown">

                                                <button type="button" class="btn btn-sm btn-icon btn-light-primary"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>


                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                                                    <li>

                                                        <a class="dropdown-item d-flex align-items-center gap-3 py-3"
                                                            href="{{ route('appointment-management.appointments.show', $appointment) }}">
                                                            <i class="bi bi-eye text-primary"></i>

                                                            <span>
                                                                View Appointment
                                                            </span>

                                                        </a>

                                                    </li>

                                                    @can('update', $appointment)
                                                        <li>

                                                            <a class="dropdown-item d-flex align-items-center gap-3 py-3"
                                                                href="{{ route('appointment-management.appointments.edit', $appointment) }}">
                                                                <i class="bi bi-pencil-square text-warning"></i>

                                                                <span>
                                                                    Edit Appointment
                                                                </span>

                                                            </a>

                                                        </li>
                                                    @endcan

                                                </ul>

                                            </div>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- Pagination --}}
                    <div
                        class="d-flex flex-column flex-lg-row
                                justify-content-between align-items-lg-center
                                gap-5 mt-8 pt-6 border-top">

                        <div class="d-flex align-items-center gap-3">

                            <div class="symbol symbol-35px">

                                <span class="symbol-label bg-light">
                                    <i class="bi bi-files text-muted"></i>
                                </span>

                            </div>

                            <div class="text-muted fs-7">

                                Page

                                <span class="fw-bold text-gray-800">
                                    {{ $appointments->currentPage() }}
                                </span>

                                of

                                <span class="fw-bold text-gray-800">
                                    {{ $appointments->lastPage() }}
                                </span>

                            </div>

                        </div>


                        <div>
                            {{ $appointments->links() }}
                        </div>

                    </div>
                @else
                    {{-- ====================================================
                        EMPTY STATE
                    ==================================================== --}}
                    <div class="text-center py-15 px-5">

                        <div class="empty-appointment-icon mb-7">

                            <i class="bi bi-calendar2-x text-primary" style="font-size: 3.5rem;"></i>

                        </div>


                        <h2 class="fw-bolder text-gray-900 mb-3">
                            No appointments found
                        </h2>


                        <div class="text-muted fs-6 mw-500px mx-auto mb-8">
                            We couldn't find any bookings matching your current
                            filters. Adjust your search criteria or create a new
                            appointment.
                        </div>


                        <div
                            class="d-flex flex-column flex-sm-row
                                    justify-content-center gap-3">

                            @if (request()->hasAny(['search', 'tenant_id', 'date', 'branch_id', 'staff_id', 'status']))
                                <a href="{{ route('appointment-management.appointments.index') }}"
                                    class="btn btn-light">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                                    Clear Filters
                                </a>
                            @endif


                            @can('create', \App\Models\Appointment::class)
                                <a href="{{ route('appointment-management.appointments.create') }}"
                                    class="btn btn-primary px-6">
                                    <i class="bi bi-calendar-plus me-2"></i>
                                    Create Appointment
                                </a>
                            @endcan

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-default-layout>
