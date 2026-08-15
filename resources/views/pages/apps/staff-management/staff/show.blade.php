<x-default-layout>

    @section('title')
        Staff Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('staff-management.staff.show', $staffMember) }}
    @endsection

    @php
        $initials = collect(explode(' ', trim($staffMember->full_name)))
            ->filter()
            ->take(2)
            ->map(fn($name) => strtoupper(substr($name, 0, 1)))
            ->implode('');

        $statusClass = match ($staffMember->status) {
            'active' => 'success',
            'terminated' => 'danger',
            'inactive' => 'secondary',
            default => 'warning',
        };

        $commissionDisplay = match ($staffMember->commission_type) {
            'percentage', 'percent' => $staffMember->commission_value . '%',
            'fixed' => number_format((float) $staffMember->commission_value, 2),
            default => $staffMember->commission_value,
        };
    @endphp

    <style>
        .staff-profile-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(114, 57, 234, .18), transparent 32%),
                linear-gradient(135deg, #ffffff 0%, #fbf9ff 55%, #f7f3ff 100%);
        }

        .staff-profile-hero::after {
            content: '';
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            right: -100px;
            bottom: -140px;
            background: rgba(114, 57, 234, .06);
        }

        .staff-avatar {
            width: 92px;
            height: 92px;
            min-width: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: linear-gradient(135deg, #7239ea, #9c6cff);
            color: #fff;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 12px 30px rgba(114, 57, 234, .22);
        }

        .staff-stat-card {
            transition: all .2s ease;
            border: 1px solid #f1f1f4 !important;
        }

        .staff-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(30, 33, 41, .08) !important;
        }

        .staff-stat-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .detail-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .section-card {
            border: 1px solid #f1f1f4 !important;
            overflow: hidden;
        }

        .section-card .card-header {
            min-height: 70px;
        }

        .branch-item,
        .service-item,
        .schedule-item,
        .break-item,
        .timeoff-item,
        .commission-item {
            transition: background-color .2s ease;
        }

        .branch-item:hover,
        .service-item:hover,
        .schedule-item:hover,
        .break-item:hover,
        .timeoff-item:hover,
        .commission-item:hover {
            background: #fafafa;
        }

        .timeline-dot {
            width: 11px;
            height: 11px;
            min-width: 11px;
            border-radius: 50%;
            background: #7239ea;
            box-shadow: 0 0 0 5px rgba(114, 57, 234, .10);
        }

        .empty-state {
            padding: 32px 20px;
            text-align: center;
            border: 1px dashed #e4e6ef;
            border-radius: 12px;
            background: #fcfcfd;
        }

        .empty-state-icon {
            width: 54px;
            height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #f1f1f4;
            margin-bottom: 12px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .staff-bio {
            max-width: 700px;
            line-height: 1.7;
        }

        .schedule-time {
            padding: 7px 12px;
            background: #f4f0ff;
            color: #7239ea;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .service-price-box {
            min-width: 105px;
        }

        @media (max-width: 767.98px) {
            .staff-avatar {
                width: 72px;
                height: 72px;
                min-width: 72px;
                font-size: 24px;
                border-radius: 20px;
            }
        }
    </style>

    <div id="kt_app_content_container" class="app-container container-xxl">

        {{-- Success Alert --}}
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-7">
                <div class="me-4">
                    <span class="staff-stat-icon bg-light-success">
                        <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                    </span>
                </div>

                <div>
                    <div class="fw-bold fs-6">Success</div>
                    <div>{{ session('status') }}</div>
                </div>
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- PROFILE HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm staff-profile-hero mb-7">
            <div class="card-body position-relative p-7 p-lg-10">

                <div class="d-flex flex-column flex-xl-row justify-content-between gap-7">

                    <div class="d-flex flex-column flex-md-row align-items-md-start gap-5">

                        {{-- Avatar --}}
                        <div class="staff-avatar">
                            {{ $initials ?: 'ST' }}
                        </div>

                        {{-- Main profile information --}}
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h1 class="fw-bolder fs-2x text-gray-900 mb-0">
                                    {{ $staffMember->full_name }}
                                </h1>

                                <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                    <span class="status-dot bg-{{ $statusClass }} me-2"></span>
                                    {{ $staffMember->status_label }}
                                </span>

                                @if ($staffMember->is_bookable)
                                    <span class="badge badge-light-primary px-3 py-2">
                                        <i class="bi bi-calendar2-check me-2"></i>
                                        Available for Booking
                                    </span>
                                @else
                                    <span class="badge badge-light px-3 py-2 text-gray-600">
                                        <i class="bi bi-calendar2-x me-2"></i>
                                        Not Bookable
                                    </span>
                                @endif
                            </div>


                            <div class="d-flex flex-wrap align-items-center gap-3 text-muted mb-4">

                                <span>
                                    <i class="bi bi-person-vcard me-1"></i>
                                    {{ $staffMember->employee_code }}
                                </span>

                                <span class="d-none d-md-inline">•</span>

                                <span>
                                    <i class="bi bi-briefcase me-1"></i>
                                    {{ $staffMember->job_title ?: 'No Job Title' }}
                                </span>

                                @if ($staffMember->tenant)
                                    <span class="d-none d-md-inline">•</span>

                                    <span>
                                        <i class="bi bi-shop me-1"></i>
                                        {{ $staffMember->tenant->name }}
                                    </span>
                                @endif

                            </div>


                            <div class="staff-bio text-gray-700">
                                {{ $staffMember->bio ?: 'No biography or additional staff notes have been added yet.' }}
                            </div>
                        </div>

                    </div>


                    {{-- Actions --}}
                    <div class="d-flex flex-wrap align-items-start gap-3">

                        <a href="{{ route('staff-management.staff.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>

                        @can('update', $staffMember)
                            <a href="{{ route('staff-management.staff.edit', $staffMember) }}" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Profile
                            </a>
                        @endcan


                        @can('delete', $staffMember)
                            <form method="POST" action="{{ route('staff-management.staff.destroy', $staffMember) }}"
                                onsubmit="return confirm('Are you sure you want to deactivate this staff member?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-light-warning">
                                    <i class="bi bi-person-dash me-2"></i>
                                    Deactivate
                                </button>
                            </form>
                        @endcan

                    </div>

                </div>

            </div>
        </div>


        {{-- ========================================================= --}}
        {{-- KPI / QUICK STATS --}}
        {{-- ========================================================= --}}
        <div class="row g-5 mb-7">

            {{-- Primary Branch --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm staff-stat-card h-100">
                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="staff-stat-icon bg-light-primary me-4">
                                <i class="bi bi-building fs-2 text-primary"></i>
                            </span>

                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    PRIMARY BRANCH
                                </div>

                                <div class="fw-bold fs-5 text-gray-900">
                                    {{ $staffMember->primaryBranch()?->name ?? 'Not Assigned' }}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>


            {{-- Assigned branches --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm staff-stat-card h-100">
                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="staff-stat-icon bg-light-info me-4">
                                <i class="bi bi-diagram-3 fs-2 text-info"></i>
                            </span>

                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    ASSIGNED BRANCHES
                                </div>

                                <div class="fw-bolder fs-2 text-gray-900">
                                    {{ $staffMember->branches->count() }}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>


            {{-- Services --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm staff-stat-card h-100">
                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="staff-stat-icon bg-light-success me-4">
                                <i class="bi bi-scissors fs-2 text-success"></i>
                            </span>

                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    SERVICES
                                </div>

                                <div class="fw-bolder fs-2 text-gray-900">
                                    {{ $staffMember->services->count() }}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>


            {{-- Login --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm staff-stat-card h-100">
                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="staff-stat-icon bg-light-warning me-4">
                                <i class="bi bi-shield-lock fs-2 text-warning"></i>
                            </span>

                            <div class="overflow-hidden">
                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    LOGIN ACCOUNT
                                </div>

                                @if ($staffMember->user)
                                    <div class="fw-bold text-gray-900 text-truncate"
                                        title="{{ $staffMember->user->email }}">
                                        {{ $staffMember->user->email }}
                                    </div>

                                    <span class="badge badge-light-success mt-2">
                                        Linked
                                    </span>
                                @else
                                    <div class="fw-bold text-gray-600">
                                        Not Linked
                                    </div>
                                @endif
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>


        <div class="row g-7">

            {{-- ===================================================== --}}
            {{-- LEFT COLUMN --}}
            {{-- ===================================================== --}}
            <div class="col-xl-6">


                {{-- OVERVIEW --}}
                <div class="card border-0 shadow-sm section-card mb-7">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Personal & Employment Details
                                </h3>

                                <div class="text-muted fs-8">
                                    Basic contact and employment information
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-3">

                        <div class="row g-5">

                            {{-- Phone --}}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">

                                    <span class="detail-icon bg-light-primary me-4">
                                        <i class="bi bi-telephone text-primary fs-5"></i>
                                    </span>

                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Phone Number
                                        </div>

                                        <div class="fw-semibold text-gray-900">
                                            {{ $staffMember->phone ?: 'Not provided' }}
                                        </div>
                                    </div>

                                </div>
                            </div>


                            {{-- Email --}}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">

                                    <span class="detail-icon bg-light-info me-4">
                                        <i class="bi bi-envelope text-info fs-5"></i>
                                    </span>

                                    <div class="overflow-hidden">
                                        <div class="text-muted fs-8 mb-1">
                                            Email Address
                                        </div>

                                        <div class="fw-semibold text-gray-900 text-truncate"
                                            title="{{ $staffMember->email }}">
                                            {{ $staffMember->email ?: 'Not provided' }}
                                        </div>
                                    </div>

                                </div>
                            </div>


                            {{-- Hire date --}}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">

                                    <span class="detail-icon bg-light-success me-4">
                                        <i class="bi bi-calendar-event text-success fs-5"></i>
                                    </span>

                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Hire Date
                                        </div>

                                        <div class="fw-semibold text-gray-900">
                                            {{ optional($staffMember->hire_date)->format('M d, Y') ?: 'Not specified' }}
                                        </div>
                                    </div>

                                </div>
                            </div>


                            {{-- Commission --}}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">

                                    <span class="detail-icon bg-light-warning me-4">
                                        <i class="bi bi-percent text-warning fs-5"></i>
                                    </span>

                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Default Commission
                                        </div>

                                        <div class="fw-bold text-gray-900">
                                            {{ $commissionDisplay }}
                                        </div>

                                        <div class="text-muted fs-9">
                                            {{ str($staffMember->commission_type)->headline() }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- BRANCHES --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm section-card mb-7">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <span class="staff-stat-icon bg-light-primary me-4">
                                <i class="bi bi-buildings text-primary fs-3"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold mb-1">
                                    Assigned Branches
                                </h3>

                                <div class="text-muted fs-8">
                                    {{ $staffMember->branches->count() }}
                                    {{ Str::plural('branch', $staffMember->branches->count()) }}
                                    assigned
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-2">

                        @forelse ($staffMember->branches as $branch)
                            <div
                                class="branch-item d-flex align-items-center justify-content-between rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex align-items-center">

                                    <span class="symbol symbol-45px me-4">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="bi bi-shop text-primary fs-3"></i>
                                        </span>
                                    </span>

                                    <div>
                                        <div class="fw-bold fs-6 text-gray-900 mb-1">
                                            {{ $branch->name }}
                                        </div>

                                        <div class="d-flex align-items-center gap-2">

                                            <span
                                                class="badge badge-light-{{ $branch->pivot->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ str($branch->pivot->status)->headline() }}
                                            </span>

                                            @if ($branch->pivot->is_primary)
                                                <span class="text-muted fs-8">
                                                    Main workplace
                                                </span>
                                            @endif

                                        </div>
                                    </div>

                                </div>


                                @if ($branch->pivot->is_primary)
                                    <span class="badge badge-light-primary px-3 py-2">
                                        <i class="bi bi-star-fill me-1"></i>
                                        Primary
                                    </span>
                                @endif

                            </div>

                        @empty

                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-building-x fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Branch Assigned
                                </div>

                                <div class="text-muted fs-8">
                                    This staff member has not been assigned to any branch.
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- SERVICES --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm section-card">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <span class="staff-stat-icon bg-light-success me-4">
                                <i class="bi bi-scissors text-success fs-3"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold mb-1">
                                    Assigned Services
                                </h3>

                                <div class="text-muted fs-8">
                                    Services this staff member can perform
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-2">

                        @forelse ($staffMember->services as $service)
                            <div
                                class="service-item d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex align-items-center">

                                    <span class="symbol symbol-45px me-4">
                                        <span class="symbol-label bg-light-success">
                                            <i class="bi bi-stars text-success fs-3"></i>
                                        </span>
                                    </span>

                                    <div>
                                        <div class="fw-bold fs-6 text-gray-900 mb-1">
                                            {{ $service->name }}
                                        </div>

                                        <span class="badge badge-light">
                                            {{ $service->category?->name ?? 'Uncategorized' }}
                                        </span>
                                    </div>

                                </div>


                                <div class="d-flex gap-3">

                                    <div class="service-price-box text-center">
                                        <div class="text-muted fs-9 mb-1">
                                            DURATION
                                        </div>

                                        <div class="fw-bold text-gray-800">
                                            @if ($service->pivot->custom_duration_minutes)
                                                {{ $service->pivot->custom_duration_minutes }} min
                                            @else
                                                Default
                                            @endif
                                        </div>
                                    </div>


                                    <div class="vr"></div>


                                    <div class="service-price-box text-center">
                                        <div class="text-muted fs-9 mb-1">
                                            PRICE
                                        </div>

                                        <div class="fw-bold text-gray-800">
                                            @if ($service->pivot->custom_price)
                                                {{ number_format((float) $service->pivot->custom_price, 2) }}
                                            @else
                                                Default
                                            @endif
                                        </div>
                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-scissors fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Services Assigned
                                </div>

                                <div class="text-muted fs-8">
                                    Assign services to make this staff member available for appointments.
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT COLUMN --}}
            {{-- ===================================================== --}}
            <div class="col-xl-6">


                {{-- ================================================= --}}
                {{-- WEEKLY SCHEDULE --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm section-card mb-7">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <span class="staff-stat-icon bg-light-primary me-4">
                                <i class="bi bi-calendar-week text-primary fs-3"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold mb-1">
                                    Weekly Schedule
                                </h3>

                                <div class="text-muted fs-8">
                                    Regular working hours
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-2">

                        @forelse ($staffMember->schedules->sortBy('day_of_week') as $schedule)
                            @php
                                $startTime = $schedule->getRawOriginal('start_time');
                                $endTime = $schedule->getRawOriginal('end_time');

                                try {
                                    $formattedStart = \Carbon\Carbon::parse($startTime)->format('h:i A');
                                    $formattedEnd = \Carbon\Carbon::parse($endTime)->format('h:i A');
                                } catch (\Throwable $e) {
                                    $formattedStart = $startTime;
                                    $formattedEnd = $endTime;
                                }
                            @endphp

                            <div
                                class="schedule-item d-flex align-items-center justify-content-between rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex align-items-center">

                                    <div class="timeline-dot me-4"></div>

                                    <div>
                                        <div class="fw-bold text-gray-900 mb-1">
                                            {{ \App\Models\Branch::DAY_LABELS[$schedule->day_of_week] ?? $schedule->day_of_week }}
                                        </div>

                                        <div class="text-muted fs-8">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            {{ $schedule->branch?->name ?? 'Branch not specified' }}
                                        </div>
                                    </div>

                                </div>


                                <div class="schedule-time">
                                    {{ $formattedStart }}
                                    <span class="mx-1">—</span>
                                    {{ $formattedEnd }}
                                </div>

                            </div>

                        @empty

                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-calendar-x fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Working Schedule
                                </div>

                                <div class="text-muted fs-8">
                                    Weekly working hours have not been configured.
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- BREAKS --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm section-card mb-7">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <span class="staff-stat-icon bg-light-warning me-4">
                                <i class="bi bi-cup-hot text-warning fs-3"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold mb-1">
                                    Break Schedule
                                </h3>

                                <div class="text-muted fs-8">
                                    Recurring unavailable periods
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-2">

                        @forelse ($staffMember->breaks->sortBy('day_of_week') as $break)
                            @php
                                $breakStart = $break->getRawOriginal('start_time');
                                $breakEnd = $break->getRawOriginal('end_time');

                                try {
                                    $formattedBreakStart = \Carbon\Carbon::parse($breakStart)->format('h:i A');
                                    $formattedBreakEnd = \Carbon\Carbon::parse($breakEnd)->format('h:i A');
                                } catch (\Throwable $e) {
                                    $formattedBreakStart = $breakStart;
                                    $formattedBreakEnd = $breakEnd;
                                }
                            @endphp

                            <div
                                class="break-item d-flex flex-column flex-md-row justify-content-between gap-3 rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex align-items-center">

                                    <span class="symbol symbol-40px me-4">
                                        <span class="symbol-label bg-light-warning">
                                            <i class="bi bi-cup-straw text-warning"></i>
                                        </span>
                                    </span>

                                    <div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $break->title ?: 'Break' }}
                                        </div>

                                        <div class="text-muted fs-8 mt-1">
                                            {{ \App\Models\Branch::DAY_LABELS[$break->day_of_week] ?? $break->day_of_week }}

                                            @if ($break->branch)
                                                · {{ $break->branch->name }}
                                            @endif
                                        </div>
                                    </div>

                                </div>


                                <div class="align-self-md-center">
                                    <span class="badge badge-light-warning px-3 py-2">
                                        {{ $formattedBreakStart }}
                                        -
                                        {{ $formattedBreakEnd }}
                                    </span>
                                </div>

                            </div>

                        @empty

                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-cup-hot fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Breaks Configured
                                </div>

                                <div class="text-muted fs-8">
                                    There are currently no recurring breaks for this staff member.
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- TIME OFF --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm section-card mb-7">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <span class="staff-stat-icon bg-light-danger me-4">
                                <i class="bi bi-calendar2-minus text-danger fs-3"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold mb-1">
                                    Time Off
                                </h3>

                                <div class="text-muted fs-8">
                                    Leave, holidays and unavailable dates
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-2">

                        @forelse ($staffMember->timeOff->sortBy('start_datetime') as $item)
                            @php
                                $timeOffStatusClass = match ($item->status) {
                                    'approved' => 'success',
                                    'rejected', 'cancelled' => 'danger',
                                    'pending' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp

                            <div
                                class="timeoff-item rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">

                                    <div>
                                        <div class="fw-bold fs-6 text-gray-900">
                                            {{ str($item->type)->replace('_', ' ')->headline() }}
                                        </div>

                                        <div class="text-muted fs-8 mt-1">
                                            {{ $item->start_datetime->format('M d, Y · h:i A') }}
                                            <span class="mx-1">→</span>
                                            {{ $item->end_datetime->format('M d, Y · h:i A') }}
                                        </div>
                                    </div>


                                    <span class="badge badge-light-{{ $timeOffStatusClass }} px-3 py-2">
                                        {{ str($item->status)->headline() }}
                                    </span>

                                </div>


                                @if ($item->reason)
                                    <div class="bg-light rounded px-4 py-3">
                                        <div class="text-muted fs-9 fw-semibold mb-1">
                                            REASON
                                        </div>

                                        <div class="text-gray-700">
                                            {{ $item->reason }}
                                        </div>
                                    </div>
                                @endif

                            </div>

                        @empty

                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-calendar-check fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Time Off Recorded
                                </div>

                                <div class="text-muted fs-8">
                                    No leave or unavailable dates have been recorded.
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- COMMISSIONS --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm section-card">

                    <div class="card-header border-0">
                        <div class="card-title">

                            <span class="staff-stat-icon bg-light-success me-4">
                                <i class="bi bi-cash-coin text-success fs-3"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold mb-1">
                                    Commission Settings
                                </h3>

                                <div class="text-muted fs-8">
                                    Service-specific earning rules
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-body pt-2">

                        @forelse ($staffMember->commissionSettings as $setting)
                            @php
                                $settingValue = in_array($setting->commission_type, ['percentage', 'percent'])
                                    ? $setting->commission_value . '%'
                                    : number_format((float) $setting->commission_value, 2);
                            @endphp

                            <div
                                class="commission-item d-flex align-items-center justify-content-between gap-3 rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex align-items-center">

                                    <span class="symbol symbol-40px me-4">
                                        <span class="symbol-label bg-light-success">
                                            <i class="bi bi-percent text-success"></i>
                                        </span>
                                    </span>

                                    <div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $setting->service?->name ?? 'Default / All Services' }}
                                        </div>

                                        <div class="text-muted fs-8">
                                            {{ str($setting->commission_type)->headline() }} Commission
                                        </div>
                                    </div>

                                </div>


                                <span class="badge badge-light-success fs-7 px-4 py-2">
                                    {{ $settingValue }}
                                </span>

                            </div>

                        @empty

                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-cash-stack fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    Default Commission Applied
                                </div>

                                <div class="text-muted fs-8">
                                    No service-specific commission overrides have been configured.
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>

            </div>

        </div>

    </div>

</x-default-layout>
