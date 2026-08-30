<x-default-layout>

    @section('title')
        {{ $staffMember->first_name }} Staff Details
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
            'on_leave' => 'warning',
            default => 'warning',
        };

        $commissionDisplay = match ($staffMember->commission_type) {
            'percentage', 'percent' => $staffMember->commission_value . '%',
            'fixed' => 'LKR ' . number_format((float) $staffMember->commission_value, 2),
            default => $staffMember->commission_value,
        };

        $primaryBranch = $staffMember->primaryBranch();

        $activeBranches = $staffMember->branches
            ->filter(fn($branch) => ($branch->pivot?->status ?? 'active') === 'active')
            ->count();

        $activeServices = $staffMember->services
            ->filter(fn($service) => ($service->pivot?->status ?? 'active') === 'active')
            ->count();

        $workingDays = $staffMember->schedules->filter(fn($schedule) => (bool) $schedule->is_working)->count();
    @endphp

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">

                <div class="symbol symbol-45px me-4">
                    <div class="symbol-label bg-light-success">
                        <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    </div>
                </div>

                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Success
                    </div>

                    <div class="text-gray-700">
                        {{ session('status') }}
                    </div>
                </div>

            </div>
        @endif

        <div class="card border-0 shadow-sm mb-5">

            <div class="card-body p-4 p-lg-6">

                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-8">

                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-6">

                        {{-- Avatar --}}
                        <div class="symbol symbol-70px symbol-circle flex-shrink-0">

                            @if ($staffMember->profile_photo)
                                <img src="{{ asset('storage/' . $staffMember->profile_photo) }}"
                                    alt="{{ $staffMember->full_name }}">
                            @else
                                <div class="symbol-label bg-light-primary text-primary fw-bolder fs-2x">
                                    {{ $initials ?: 'ST' }}
                                </div>
                            @endif

                        </div>


                        {{-- Main Info --}}
                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-1">

                                <h1 class="fw-bolder text-gray-900 mb-0">
                                    {{ $staffMember->full_name }}
                                </h1>


                                <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                    <i class="bi bi-circle-fill fs-9 me-2"></i>
                                    {{ $staffMember->status_label }}
                                </span>


                                @if ($staffMember->is_bookable)
                                    <span class="badge badge-light-success px-3 py-2">
                                        <i class="bi bi-calendar2-check me-2"></i>
                                        Bookable
                                    </span>
                                @else
                                    <span class="badge badge-light-secondary px-3 py-2">
                                        <i class="bi bi-calendar2-x me-2"></i>
                                        Not Bookable
                                    </span>
                                @endif


                                @if ($staffMember->show_online)
                                    <span class="badge badge-light-info px-3 py-2">
                                        <i class="bi bi-globe2 me-2"></i>
                                        Online Profile
                                    </span>
                                @endif

                            </div>


                            <div class="d-flex flex-wrap gap-4 text-muted fs-7">

                                <span>
                                    <i class="bi bi-person-vcard me-1"></i>
                                    {{ $staffMember->employee_code }}
                                </span>

                                <span>
                                    <i class="bi bi-briefcase me-1"></i>
                                    {{ $staffMember->job_title ?: 'No Job Title' }}
                                </span>

                                @if ($staffMember->tenant)
                                    <span>
                                        <i class="bi bi-shop me-1"></i>
                                        {{ $staffMember->tenant->name }}
                                    </span>
                                @endif

                            </div>


                            <div class="text-gray-700 fs-7">
                                {{ $staffMember->bio ?: 'No biography or additional staff notes have been added yet.' }}
                            </div>

                        </div>

                    </div>


                    {{-- Actions --}}
                    <div class="d-flex flex-wrap gap-3">

                        <a href="{{ route('staff-management.staff.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>


                        @can('update', $staffMember)
                            <a href="{{ route('staff-management.staff.edit', $staffMember) }}"
                                class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Profile
                            </a>
                        @endcan


                        @can('delete', $staffMember)
                            <form method="POST" action="{{ route('staff-management.staff.destroy', $staffMember) }}"
                                data-swal-confirm data-swal-title="Deactivate {{ $staffMember->full_name }}?"
                                data-swal-text="They will no longer be available for bookings until reactivated."
                                data-swal-icon="warning" data-swal-confirm-button="Yes, deactivate"
                                data-swal-cancel-button="Keep active">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-light-danger btn-sm">
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
        {{-- QUICK OVERVIEW --}}
        {{-- ========================================================= --}}
        <div class="row g-5 mb-8">

            {{-- Primary Branch --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-building text-primary fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-primary">
                                Main
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">
                            Primary Branch
                        </div>

                        <div class="fw-bold text-gray-900 fs-5">
                            {{ $primaryBranch?->name ?? 'Not Assigned' }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Active Branches --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-diagram-3 text-info fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-info">
                                Locations
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Active Branches
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($activeBranches) }}
                        </div>

                        <div class="text-muted fs-8 mt-1">
                            of {{ number_format($staffMember->branches->count()) }} assigned
                        </div>

                    </div>

                </div>

            </div>


            {{-- Active Services --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-scissors text-success fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-success">
                                Skills
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Active Services
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($activeServices) }}
                        </div>

                        <div class="text-muted fs-8 mt-1">
                            of {{ number_format($staffMember->services->count()) }} assigned
                        </div>

                    </div>

                </div>

            </div>


            {{-- Working Days --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-calendar-week text-warning fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-warning">
                                Schedule
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Working Days
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($workingDays) }}
                        </div>

                        <div class="text-muted fs-8 mt-1">
                            Regular weekly schedule
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- MAIN GRID --}}
        {{-- ========================================================= --}}
        <div class="row g-8">

            {{-- ===================================================== --}}
            {{-- LEFT COLUMN --}}
            {{-- ===================================================== --}}
            <div class="col-xl-5">


                {{-- ================================================= --}}
                {{-- PERSONAL DETAILS --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-person-lines-fill text-primary fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Personal & Employment
                                </h3>

                                <div class="text-muted fs-8">
                                    Staff contact and employment information.
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        <div class="d-flex flex-column gap-6">

                            {{-- Phone --}}
                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-telephone text-primary fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Phone Number
                                    </div>

                                    <div class="fw-semibold text-gray-900">
                                        {{ $staffMember->phone ?: 'Not provided' }}
                                    </div>
                                </div>

                            </div>


                            <div class="separator separator-dashed"></div>


                            {{-- Email --}}
                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-envelope text-info fs-4"></i>
                                    </div>
                                </div>

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


                            <div class="separator separator-dashed"></div>


                            {{-- Hire Date --}}
                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-calendar-event text-success fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Hire Date
                                    </div>

                                    <div class="fw-semibold text-gray-900">
                                        {{ optional($staffMember->hire_date)->format('M d, Y') ?: 'Not specified' }}
                                    </div>
                                </div>

                            </div>


                            <div class="separator separator-dashed"></div>


                            {{-- DOB --}}
                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-cake2 text-warning fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Date of Birth
                                    </div>

                                    <div class="fw-semibold text-gray-900">
                                        {{ optional($staffMember->date_of_birth)->format('M d, Y') ?: 'Not specified' }}
                                    </div>
                                </div>

                            </div>


                            <div class="separator separator-dashed"></div>


                            {{-- Gender --}}
                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-person text-info fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Gender
                                    </div>

                                    <div class="fw-semibold text-gray-900">
                                        {{ $staffMember->gender ? str($staffMember->gender)->replace('_', ' ')->headline() : 'Not specified' }}
                                    </div>
                                </div>

                            </div>


                            <div class="separator separator-dashed"></div>


                            {{-- Login --}}
                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-danger">
                                        <i class="bi bi-shield-lock text-danger fs-4"></i>
                                    </div>
                                </div>

                                <div class="overflow-hidden">

                                    <div class="text-muted fs-8 mb-1">
                                        Login Account
                                    </div>

                                    @if ($staffMember->user)
                                        <div class="fw-semibold text-gray-900 text-truncate"
                                            title="{{ $staffMember->user->email }}">
                                            {{ $staffMember->user->email }}
                                        </div>

                                        <span class="badge badge-light-success mt-2">
                                            Linked
                                        </span>
                                    @else
                                        <div class="fw-semibold text-gray-600">
                                            No system login
                                        </div>
                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- COMMISSION SUMMARY --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-6">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-cash-stack text-success fs-3"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="fw-bold text-gray-900">
                                        Default Commission
                                    </div>

                                    <div class="text-muted fs-8">
                                        Base earning rule
                                    </div>
                                </div>

                            </div>

                            <span class="badge badge-light-success fs-6 px-4 py-3">
                                {{ $commissionDisplay }}
                            </span>

                        </div>


                        <div class="rounded-4 bg-light-success p-5">

                            <div class="text-muted fs-8 mb-1">
                                Commission Type
                            </div>

                            <div class="fw-bold text-gray-900">
                                {{ str($staffMember->commission_type)->headline() }}
                            </div>

                            <div class="text-muted fs-8 mt-2">
                                Used whenever there is no service-specific override.
                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- BRANCHES --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-buildings text-primary fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Assigned Branches
                                </h3>

                                <div class="text-muted fs-8">
                                    Work locations for this employee.
                                </div>
                            </div>

                        </div>


                        <div class="card-toolbar">

                            <span class="badge badge-light-primary px-3 py-2">
                                {{ number_format($staffMember->branches->count()) }}
                            </span>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($staffMember->branches as $branch)

                            @php
                                $branchStatusClass =
                                    ($branch->pivot?->status ?? 'active') === 'active' ? 'success' : 'secondary';
                            @endphp

                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div class="d-flex align-items-center justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-45px me-4">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-shop text-primary fs-3"></i>
                                            </div>
                                        </div>

                                        <div>

                                            <div class="fw-bold text-gray-900 mb-2">
                                                {{ $branch->name }}
                                            </div>

                                            <div class="d-flex flex-wrap gap-2">

                                                <span class="badge badge-light-{{ $branchStatusClass }}">
                                                    {{ str($branch->pivot?->status ?? 'active')->headline() }}
                                                </span>

                                                @if ($branch->pivot?->is_primary)
                                                    <span class="badge badge-light-warning">
                                                        <i class="bi bi-star-fill me-1"></i>
                                                        Primary
                                                    </span>
                                                @endif

                                            </div>

                                        </div>

                                    </div>


                                    @if ($branch->pivot?->is_primary)
                                        <div class="symbol symbol-35px">
                                            <div class="symbol-label bg-light-warning">
                                                <i class="bi bi-star-fill text-warning"></i>
                                            </div>
                                        </div>
                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-12">

                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light rounded-circle">
                                        <i class="bi bi-building-x text-muted fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Branch Assigned
                                </h4>

                                <div class="text-muted fs-7">
                                    This staff member has not been assigned to a branch.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT COLUMN --}}
            {{-- ===================================================== --}}
            <div class="col-xl-7">


                {{-- ================================================= --}}
                {{-- SERVICES --}}
                {{-- ================================================= --}}
                <div id="services" class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-scissors text-success fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Assigned Services
                                </h3>

                                <div class="text-muted fs-8">
                                    Services this staff member can perform.
                                </div>
                            </div>

                        </div>


                        <div class="card-toolbar">

                            <span class="badge badge-light-success px-3 py-2">
                                {{ number_format($staffMember->services->count()) }}
                                {{ Str::plural('Service', $staffMember->services->count()) }}
                            </span>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($staffMember->services as $service)
                            @php
                                $serviceActive = ($service->pivot?->status ?? 'active') === 'active';

                                $effectiveDuration =
                                    $service->pivot?->custom_duration_minutes ?:
                                    $service->default_duration_minutes ?? $service->duration_minutes;

                                $effectivePrice =
                                    $service->pivot?->custom_price ?? ($service->default_price ?? $service->price);

                                $customized =
                                    !is_null($service->pivot?->custom_duration_minutes) ||
                                    !is_null($service->pivot?->custom_price);
                            @endphp


                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-50px me-4">
                                            <div class="symbol-label bg-light-success">
                                                <i class="bi bi-stars text-success fs-3"></i>
                                            </div>
                                        </div>

                                        <div>

                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                                                <div class="fw-bold text-gray-900 fs-6">
                                                    {{ $service->name }}
                                                </div>

                                                <span
                                                    class="badge {{ $serviceActive ? 'badge-light-success' : 'badge-light-secondary' }}">
                                                    {{ $serviceActive ? 'Active' : 'Inactive' }}
                                                </span>

                                                @if ($customized)
                                                    <span class="badge badge-light-warning">
                                                        Customized
                                                    </span>
                                                @endif

                                            </div>

                                            <span class="badge badge-light">
                                                {{ $service->category?->name ?? 'Uncategorized' }}
                                            </span>

                                        </div>

                                    </div>


                                    <div class="d-flex flex-wrap align-items-center gap-6">

                                        <div>

                                            <div class="text-muted fs-9 fw-semibold text-uppercase mb-1">
                                                Duration
                                            </div>

                                            <div class="fw-bold text-gray-900">
                                                {{ $effectiveDuration ? $effectiveDuration . ' min' : 'Not Set' }}
                                            </div>

                                            <div class="text-muted fs-9">
                                                {{ $service->pivot?->custom_duration_minutes ? 'Override' : 'Default' }}
                                            </div>

                                        </div>


                                        <div class="border-start h-50px"></div>


                                        <div>

                                            <div class="text-muted fs-9 fw-semibold text-uppercase mb-1">
                                                Price
                                            </div>

                                            <div class="fw-bold text-gray-900">
                                                {{ !is_null($effectivePrice) ? 'LKR ' . number_format((float) $effectivePrice, 2) : 'Not Set' }}
                                            </div>

                                            <div class="text-muted fs-9">
                                                {{ !is_null($service->pivot?->custom_price) ? 'Override' : 'Default' }}
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-12">

                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light rounded-circle">
                                        <i class="bi bi-scissors text-muted fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Services Assigned
                                </h4>

                                <div class="text-muted fs-7">
                                    Assign services before accepting appointments for this staff member.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- WEEKLY SCHEDULE --}}
                {{-- ================================================= --}}
                <div id="schedule" class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-calendar-week text-primary fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Weekly Schedule
                                </h3>

                                <div class="text-muted fs-8">
                                    Regular weekly working hours.
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($staffMember->schedules->sortBy('day_of_week')
                            as $schedule)
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


                            <div class="py-4 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div
                                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-40px me-4">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-calendar-day text-primary"></i>
                                            </div>
                                        </div>

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


                                    @if ($schedule->is_working)
                                        <span class="badge badge-light-primary px-3 py-2">
                                            <i class="bi bi-clock me-2"></i>
                                            {{ $formattedStart }} – {{ $formattedEnd }}
                                        </span>
                                    @else
                                        <span class="badge badge-light-secondary px-3 py-2">
                                            <i class="bi bi-moon me-2"></i>
                                            Day Off
                                        </span>
                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-12">

                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light rounded-circle">
                                        <i class="bi bi-calendar-x text-muted fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Working Schedule
                                </h4>

                                <div class="text-muted fs-7">
                                    Weekly working hours have not been configured.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- BREAKS & TIME OFF --}}
                {{-- ================================================= --}}
                <div class="row g-8 mb-8">

                    {{-- Breaks --}}
                    <div class="col-lg-6">

                        <div class="card border-0 shadow-sm h-100">

                            <div class="card-header border-0 pt-8">

                                <div class="card-title">

                                    <div class="symbol symbol-40px me-4">
                                        <div class="symbol-label bg-light-warning">
                                            <i class="bi bi-cup-hot text-warning"></i>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="fw-bold text-gray-900 mb-1">
                                            Break Schedule
                                        </h3>

                                        <div class="text-muted fs-8">
                                            Recurring unavailable periods.
                                        </div>
                                    </div>

                                </div>

                            </div>


                            <div class="card-body pt-3">

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


                                    <div class="py-4 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                        <div class="fw-bold text-gray-900 mb-2">
                                            {{ $break->title ?: 'Break' }}
                                        </div>

                                        <div class="text-muted fs-8 mb-3">

                                            {{ \App\Models\Branch::DAY_LABELS[$break->day_of_week] ?? $break->day_of_week }}

                                            @if ($break->branch)
                                                · {{ $break->branch->name }}
                                            @endif

                                        </div>

                                        <span class="badge badge-light-warning px-3 py-2">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $formattedBreakStart }} – {{ $formattedBreakEnd }}
                                        </span>

                                    </div>

                                @empty

                                    <div class="text-center py-10">

                                        <div class="symbol symbol-60px mb-4">
                                            <div class="symbol-label bg-light rounded-circle">
                                                <i class="bi bi-cup-hot text-muted fs-2"></i>
                                            </div>
                                        </div>

                                        <div class="fw-bold text-gray-900 mb-1">
                                            No Breaks
                                        </div>

                                        <div class="text-muted fs-8">
                                            No recurring breaks configured.
                                        </div>

                                    </div>
                                @endforelse

                            </div>

                        </div>

                    </div>


                    {{-- Time Off --}}
                    <div class="col-lg-6">

                        <div class="card border-0 shadow-sm h-100">

                            <div class="card-header border-0 pt-8">

                                <div class="card-title">

                                    <div class="symbol symbol-40px me-4">
                                        <div class="symbol-label bg-light-danger">
                                            <i class="bi bi-calendar2-minus text-danger"></i>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="fw-bold text-gray-900 mb-1">
                                            Time Off
                                        </h3>

                                        <div class="text-muted fs-8">
                                            Leave and unavailable dates.
                                        </div>
                                    </div>

                                </div>

                            </div>


                            <div class="card-body pt-3">

                                @forelse ($staffMember->timeOff->sortBy('start_datetime') as $item)
                                    @php
                                        $timeOffStatusClass = match ($item->status) {
                                            'approved' => 'success',
                                            'rejected', 'cancelled' => 'danger',
                                            'pending' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp


                                    <div class="py-4 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                        <div class="d-flex justify-content-between gap-3 mb-2">

                                            <div class="fw-bold text-gray-900">
                                                {{ str($item->type)->replace('_', ' ')->headline() }}
                                            </div>

                                            <span class="badge badge-light-{{ $timeOffStatusClass }}">
                                                {{ str($item->status)->headline() }}
                                            </span>

                                        </div>


                                        <div class="text-muted fs-8 mb-3">
                                            {{ $item->start_datetime->format('M d, Y · h:i A') }}
                                            <br>
                                            {{ $item->end_datetime->format('M d, Y · h:i A') }}
                                        </div>


                                        @if ($item->reason)
                                            <div class="rounded-3 bg-light p-3">

                                                <div class="text-muted fs-9 fw-semibold text-uppercase mb-1">
                                                    Reason
                                                </div>

                                                <div class="text-gray-700 fs-8">
                                                    {{ $item->reason }}
                                                </div>

                                            </div>
                                        @endif

                                    </div>

                                @empty

                                    <div class="text-center py-10">

                                        <div class="symbol symbol-60px mb-4">
                                            <div class="symbol-label bg-light rounded-circle">
                                                <i class="bi bi-calendar-check text-muted fs-2"></i>
                                            </div>
                                        </div>

                                        <div class="fw-bold text-gray-900 mb-1">
                                            No Time Off
                                        </div>

                                        <div class="text-muted fs-8">
                                            No unavailable dates recorded.
                                        </div>

                                    </div>
                                @endforelse

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- COMMISSION OVERRIDES --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-percent text-success fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Service Commission Overrides
                                </h3>

                                <div class="text-muted fs-8">
                                    Special commission rules by service.
                                </div>
                            </div>

                        </div>


                        <div class="card-toolbar">

                            <span class="badge badge-light-success px-3 py-2">
                                {{ number_format($staffMember->commissionSettings->count()) }}
                                Overrides
                            </span>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($staffMember->commissionSettings as $setting)
                            @php
                                $settingValue = in_array($setting->commission_type, ['percentage', 'percent'])
                                    ? $setting->commission_value . '%'
                                    : 'LKR ' . number_format((float) $setting->commission_value, 2);
                            @endphp


                            <div class="py-4 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div class="d-flex align-items-center justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-40px me-4">
                                            <div class="symbol-label bg-light-success">
                                                <i class="bi bi-cash-coin text-success"></i>
                                            </div>
                                        </div>

                                        <div>

                                            <div class="fw-bold text-gray-900 mb-1">
                                                {{ $setting->service?->name ?? 'Default / All Services' }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                {{ str($setting->commission_type)->headline() }}
                                                commission
                                            </div>

                                        </div>

                                    </div>


                                    <span class="badge badge-light-success fs-7 px-4 py-2">
                                        {{ $settingValue }}
                                    </span>

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-12">

                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light-success rounded-circle">
                                        <i class="bi bi-cash-stack text-success fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    Default Commission Applied
                                </h4>

                                <div class="text-muted fs-7 mb-4">
                                    No service-specific commission overrides are configured.
                                </div>

                                <span class="badge badge-light-success px-4 py-2 fs-7">
                                    {{ $commissionDisplay }}
                                    {{ str($staffMember->commission_type)->headline() }}
                                </span>

                            </div>
                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>


    @include('pages.apps.staff-management.staff._sweet-alerts')

</x-default-layout>
