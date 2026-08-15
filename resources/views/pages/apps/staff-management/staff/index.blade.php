<x-default-layout>

    @section('title')
        Staff Management
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('staff-management.staff.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div
                class="alert alert-dismissible bg-light-success border border-success border-dashed d-flex flex-column flex-sm-row p-5 mb-8">
                <i class="ki-duotone ki-check-circle fs-2hx text-success me-4 mb-3 mb-sm-0">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1 text-success">Success</h5>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="alert alert-dismissible bg-light-danger border border-danger border-dashed d-flex flex-column flex-sm-row p-5 mb-8">
                <i class="ki-duotone ki-information fs-2hx text-danger me-4 mb-3 mb-sm-0">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1 text-danger">Something went wrong</h5>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <div class="card border-0 bg-light-primary mb-8 overflow-hidden">
            <div class="card-body py-8 px-8 px-lg-10">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-6">
                    <div class="d-flex align-items-center gap-5">
                        <div class="symbol symbol-70px">
                            <div class="symbol-label bg-primary">
                                <i class="bi bi-people-fill fs-1 text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">
                                Staff Management
                            </div>
                            <h1 class="fw-bolder text-gray-900 mb-2">
                                Your Team
                            </h1>
                            <div class="text-gray-600">
                                Manage staff profiles, branches, services and booking availability.
                            </div>
                        </div>
                    </div>
                    @can('create', \App\Models\Staff::class)
                        <div>
                            <a href="{{ route('staff-management.staff.create') }}" class="btn btn-primary btn-lg px-7">
                                <i class="bi bi-person-plus-fill me-2"></i>
                                Add Staff Member
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-5">
                        <div class="symbol symbol-55px">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-people fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-gray-900">
                                {{ $staff->total() }}
                            </div>
                            <div class="text-muted fw-semibold fs-7">
                                Total Staff
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-5">
                        <div class="symbol symbol-55px">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-person-check fs-2 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-gray-900">
                                {{ $staff->getCollection()->where('status', 'active')->count() }}
                            </div>
                            <div class="text-muted fw-semibold fs-7">
                                Active on Page
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-5">
                        <div class="symbol symbol-55px">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-calendar-check fs-2 text-info"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-gray-900">
                                {{ $staff->getCollection()->where('is_bookable', true)->count() }}
                            </div>
                            <div class="text-muted fw-semibold fs-7">
                                Bookable on Page
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-5">
                        <div class="symbol symbol-55px">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-building fs-2 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-gray-900">
                                {{ $branches->count() }}
                            </div>
                            <div class="text-muted fw-semibold fs-7">
                                Available Branches
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-40px">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-funnel text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold text-gray-900 mb-0">
                                Find Staff
                            </h3>
                            <div class="text-muted fs-8">
                                Search and filter your team
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <form method="GET" action="{{ route('staff-management.staff.index') }}"
                    class="row g-5 align-items-end">

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Search Staff
                        </label>
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                            <input type="search" name="search" value="{{ request('search') }}"
                                class="form-control form-control-solid ps-11" placeholder="Name, code or phone">
                        </div>
                    </div>

                    @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label fw-semibold text-gray-700">
                                Salon
                            </label>
                            <select name="tenant_id" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All salons</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Branch
                        </label>
                        <select name="branch_id" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Status
                        </label>
                        <select name="status" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\Staff::STATUSES as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ str($status)->replace('_', ' ')->headline() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Booking
                        </label>
                        <select name="bookable" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All</option>
                            <option value="yes" @selected(request('bookable') === 'yes')>
                                Bookable
                            </option>
                            <option value="no" @selected(request('bookable') === 'no')>
                                Not Bookable
                            </option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-end gap-3">
                            <a href="{{ route('staff-management.staff.index') }}" class="btn btn-light">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                Reset
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel-fill me-2"></i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Staff Directory --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-7 pb-2">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Staff Directory
                        </h3>
                        <div class="text-muted fs-7">
                            {{ $staff->total() }}
                            {{ Str::plural('staff member', $staff->total()) }}
                            found
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-6">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th class="min-w-250px">
                                    Employee
                                </th>
                                @if ($isSuperAdmin ?? false)
                                    <th class="min-w-170px">
                                        Salon
                                    </th>
                                @endif
                                <th class="min-w-180px">
                                    Branch
                                </th>
                                <th class="min-w-160px">
                                    Position
                                </th>
                                <th class="min-w-100px text-center">
                                    Services
                                </th>
                                <th class="min-w-110px">
                                    Booking
                                </th>
                                <th class="min-w-120px">
                                    Status
                                </th>
                                <th class="text-end min-w-120px">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($staff as $member)
                                @php
                                    $primaryBranch = $member->primaryBranch();
                                    $statusClass = match ($member->status) {
                                        'active' => 'success',
                                        'terminated' => 'danger',
                                        'on_leave' => 'warning',
                                        'inactive' => 'secondary',
                                        default => 'secondary',
                                    };
                                    $initials = strtoupper(
                                        substr($member->first_name ?? '', 0, 1) .
                                            substr($member->last_name ?? '', 0, 1),
                                    );
                                @endphp

                                <tr>
                                    {{-- Employee --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-55px symbol-circle me-4">
                                                @if ($member->profile_photo)
                                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                        alt="{{ $member->full_name }}">
                                                @else
                                                    <div
                                                        class="symbol-label bg-light-primary text-primary fw-bold fs-5">
                                                        {{ $initials ?: 'ST' }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="d-flex flex-column">
                                                <a href="{{ route('staff-management.staff.show', $member) }}"
                                                    class="text-gray-900 text-hover-primary fw-bold fs-6 mb-1">
                                                    {{ $member->full_name }}
                                                </a>

                                                <div class="d-flex align-items-center gap-2 text-muted fs-8">
                                                    <span>
                                                        <i class="bi bi-person-vcard me-1"></i>
                                                        {{ $member->employee_code }}
                                                    </span>
                                                </div>
                                                @if ($member->phone)
                                                    <div class="text-muted fs-8 mt-1">
                                                        <i class="bi bi-telephone me-1"></i>
                                                        {{ $member->phone }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Tenant --}}
                                    @if ($isSuperAdmin ?? false)
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="symbol symbol-30px">
                                                    <span class="symbol-label bg-light-info">
                                                        <i class="bi bi-shop text-info fs-7"></i>
                                                    </span>
                                                </span>
                                                <span class="text-gray-800">
                                                    {{ $member->tenant?->name ?? '-' }}
                                                </span>
                                            </div>
                                        </td>
                                    @endif

                                    {{-- Branch --}}
                                    <td>
                                        @if ($primaryBranch)
                                            <div class="d-flex flex-column">
                                                <span class="text-gray-900 fw-bold">
                                                    {{ $primaryBranch->name }}
                                                </span>
                                                <span class="text-muted fs-8">
                                                    Primary branch
                                                </span>
                                            </div>
                                        @elseif ($member->branches->isNotEmpty())
                                            <span class="text-gray-900">
                                                {{ $member->branches->pluck('name')->take(2)->implode(', ') }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                Not assigned
                                            </span>
                                        @endif

                                        @if ($member->branches_count > 1)
                                            <span class="badge badge-light-primary ms-1">
                                                +{{ $member->branches_count - 1 }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Position --}}
                                    <td>
                                        @if ($member->job_title)
                                            <span class="badge badge-light-info fw-semibold px-3 py-2">
                                                {{ $member->job_title }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                —
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Services --}}
                                    <td class="text-center">
                                        <a href="{{ route('staff-management.staff.show', $member) }}"
                                            class="badge badge-light-primary px-3 py-2">
                                            <i class="bi bi-scissors me-1"></i>
                                            {{ $member->services_count }}
                                        </a>
                                    </td>

                                    {{-- Booking --}}
                                    <td>
                                        @if ($member->is_bookable)
                                            <span class="badge badge-light-success px-3 py-2">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                Bookable
                                            </span>
                                        @else
                                            <span class="badge badge-light-secondary px-3 py-2">
                                                <i class="bi bi-calendar-x me-1"></i>
                                                Disabled
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                            <span class="bullet bullet-dot bg-{{ $statusClass }} me-2"></span>
                                            {{ $member->status_label }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light btn-active-light-primary"
                                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                            Actions
                                            <i class="ki-duotone ki-down fs-5 ms-1"></i>
                                        </button>

                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                            data-kt-menu="true">
                                            <div class="menu-item px-3">
                                                <a href="{{ route('staff-management.staff.show', $member) }}"
                                                    class="menu-link px-3">
                                                    <i class="bi bi-eye me-3"></i>
                                                    View Profile
                                                </a>
                                            </div>
                                            @can('update', $member)
                                                <div class="menu-item px-3">
                                                    <a href="{{ route('staff-management.staff.edit', $member) }}"
                                                        class="menu-link px-3">
                                                        <i class="bi bi-pencil-square me-3"></i>
                                                        Edit Staff
                                                    </a>
                                                </div>
                                            @endcan

                                            <div class="separator my-2"></div>

                                            <div class="menu-item px-3">
                                                <a href="{{ route('staff-management.staff.show', $member) }}#services"
                                                    class="menu-link px-3">
                                                    <i class="bi bi-scissors me-3"></i>
                                                    Services
                                                </a>
                                            </div>

                                            <div class="menu-item px-3">
                                                <a href="{{ route('staff-management.staff.show', $member) }}#schedule"
                                                    class="menu-link px-3">
                                                    <i class="bi bi-clock me-3"></i>
                                                    Schedule
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ?? false ? 8 : 7 }}" class="text-center py-15">
                                        <div class="mb-5">
                                            <div class="symbol symbol-90px">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-people fs-1 text-primary"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <h3 class="text-gray-900 fw-bold mb-2">
                                            No staff members found
                                        </h3>
                                        <div class="text-muted mb-6">
                                            Try adjusting your filters or create your first staff member.
                                        </div>

                                        @can('create', \App\Models\Staff::class)
                                            <a href="{{ route('staff-management.staff.create') }}"
                                                class="btn btn-primary">
                                                <i class="bi bi-person-plus me-2"></i>
                                                Add Staff Member
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($staff->hasPages())
                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4 pt-6">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-bold text-gray-800">
                                {{ $staff->firstItem() }}
                            </span>
                            to
                            <span class="fw-bold text-gray-800">
                                {{ $staff->lastItem() }}
                            </span>
                            of
                            <span class="fw-bold text-gray-800">
                                {{ $staff->total() }}
                            </span>
                            staff members
                        </div>
                        <div>
                            {{ $staff->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
