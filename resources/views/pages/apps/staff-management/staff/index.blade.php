<x-default-layout>

    @section('title')
        Staff Management
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('staff-management.staff.index') }}
    @endsection

    @php
        $activeFilterCount = collect(['tenant_id', 'branch_id', 'status', 'bookable'])
            ->filter(fn(string $filter) => request()->filled($filter))
            ->count();
        $hasActiveFilters = request()->filled('search') || $activeFilterCount > 0;
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
                    <div>
                        {{ session('status') }}
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">
                <div class="symbol symbol-45px me-4 flex-shrink-0">
                    <div class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Something went wrong
                    </div>
                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-50px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-people-fill text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Staff Management
                                </h3>
                            </div>
                            <div class="text-muted fs-6">
                                Manage staff profiles, branches, services and booking availability.
                            </div>
                        </div>
                    </div>
                    @can('create', \App\Models\Staff::class)
                        <a href="{{ route('staff-management.staff.create') }}"
                            class="btn btn-primary align-self-center btn-sm">
                            <i class="bi bi-person-plus-fill me-2"></i>
                            Add Staff Member
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-person-lines-fill text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Staff Directory
                        </h3>
                        <div class="text-muted fs-8">
                            {{ number_format($staff->total()) }}
                            {{ Str::plural('staff member', $staff->total()) }}
                            found
                        </div>
                    </div>
                </div>

                <div class="card-toolbar">
                    <form id="staffFilterForm" method="GET" action="{{ route('staff-management.staff.index') }}"
                        class="d-flex flex-wrap align-items-center gap-3">
                        <div class="position-relative">
                            <i
                                class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted fs-5"></i>
                            <input type="search" name="search" id="staff-search-input" value="{{ request('search') }}"
                                autocomplete="off"
                                class="form-control form-control-solid ps-12 pe-12 w-225px w-md-300px"
                                placeholder="Search staff..." data-staff-realtime-search
                                data-original-value="{{ request('search') }}">
                            <span id="staff-search-spinner"
                                class="spinner-border spinner-border-sm text-primary position-absolute top-50 translate-middle-y end-0 me-4 d-none"
                                role="status" aria-hidden="true"></span>
                        </div>

                        <div>
                            <button type="button"
                                class="btn btn-light-primary d-flex align-items-center position-relative"
                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="bi bi-funnel-fill me-2"></i>
                                Filter
                                @if ($activeFilterCount > 0)
                                    <span
                                        class="badge badge-circle badge-danger position-absolute top-0 start-100 translate-middle p-0"
                                        style="width: 10px; height: 10px;"></span>
                                @endif
                            </button>

                            <div class="menu menu-sub menu-sub-dropdown menu-column w-300px w-md-375px p-6"
                                data-kt-menu="true">
                                <div class="fs-5 text-gray-900 fw-bold mb-5">
                                    Filter Staff
                                </div>

                                <div class="row g-4">
                                    @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-gray-700 fs-8">
                                                Salon
                                            </label>
                                            <select name="tenant_id" class="form-select form-select-sm"
                                                data-control="select2" data-hide-search="true">
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

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Branch
                                        </label>
                                        <select name="branch_id" class="form-select form-select-sm"
                                            data-control="select2" data-hide-search="true">
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

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Status
                                        </label>
                                        <select name="status" class="form-select form-select-sm" data-control="select2"
                                            data-hide-search="true">
                                            <option value="">
                                                All statuses
                                            </option>
                                            @foreach (\App\Models\Staff::STATUSES as $status)
                                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                                    {{ str($status)->replace('_', ' ')->headline() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Booking
                                        </label>
                                        <select name="bookable" class="form-select form-select-sm"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                All
                                            </option>
                                            <option value="yes" @selected(request('bookable') === 'yes')}>
                                                Bookable
                                            </option>
                                            <option value="no" @selected(request('bookable') === 'no')}>
                                                Not Bookable
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-6">
                                    <a href="{{ route('staff-management.staff.index') }}" class="btn btn-sm btn-light">
                                        Reset
                                    </a>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-funnel-fill me-2"></i>
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body pt-4">
                @if ($staff->isNotEmpty())

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-260px">
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

                                    <th class="min-w-120px">
                                        Booking
                                    </th>

                                    <th class="min-w-120px">
                                        Status
                                    </th>

                                    <th class="text-end min-w-130px">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($staff as $member)
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

                                                <div class="symbol symbol-55px symbol-circle me-4 flex-shrink-0">

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


                                                    <div
                                                        class="d-flex flex-wrap align-items-center gap-3 text-muted fs-8">

                                                        <span>
                                                            <i class="bi bi-person-vcard me-1"></i>
                                                            {{ $member->employee_code }}
                                                        </span>

                                                        @if ($member->phone)
                                                            <span>
                                                                <i class="bi bi-telephone me-1"></i>
                                                                {{ $member->phone }}
                                                            </span>
                                                        @endif

                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>

                                                @if ($member->tenant)
                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="bi bi-shop text-info"></i>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div class="fw-semibold text-gray-900">
                                                                {{ $member->tenant->name }}
                                                            </div>

                                                            <div class="text-muted fs-8">
                                                                Salon
                                                            </div>
                                                        </div>

                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif

                                            </td>
                                        @endif


                                        {{-- Branch --}}
                                        <td>

                                            @if ($primaryBranch)
                                                <div class="d-flex align-items-center">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-primary">
                                                            <i class="bi bi-building text-primary"></i>
                                                        </div>
                                                    </div>

                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            {{ $primaryBranch->name }}
                                                        </div>

                                                        <div class="text-muted fs-8">
                                                            Primary branch
                                                        </div>

                                                    </div>

                                                </div>
                                            @elseif ($member->branches->isNotEmpty())
                                                <div class="fw-semibold text-gray-900">
                                                    {{ $member->branches->pluck('name')->take(2)->implode(', ') }}
                                                </div>
                                            @else
                                                <span class="badge badge-light">
                                                    Not Assigned
                                                </span>
                                            @endif


                                            @if ($member->branches_count > 1)
                                                <span class="badge badge-light-primary mt-2">
                                                    +{{ $member->branches_count - 1 }}
                                                    more
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Position --}}
                                        <td>

                                            @if ($member->job_title)
                                                <span class="badge badge-light-info px-3 py-2">
                                                    <i class="bi bi-briefcase me-1"></i>
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

                                            <a href="{{ route('staff-management.staff.show', $member) }}#services"
                                                class="badge badge-light-primary px-3 py-2">
                                                <i class="bi bi-scissors me-1"></i>
                                                {{ number_format($member->services_count) }}
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

                                                <i class="bi bi-circle-fill fs-9 me-2"></i>

                                                {{ $member->status_label }}

                                            </span>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <button type="button"
                                                class="btn btn-sm btn-light btn-active-light-primary"
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
                                @endforeach

                            </tbody>

                        </table>

                    </div>

                    @if ($staff->hasPages())
                        <div
                            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 border-top border-gray-200 pt-6 mt-6">
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
                                    {{ number_format($staff->total()) }}
                                </span>
                                staff members
                            </div>
                            <div>
                                {{ $staff->withQueryString()->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-15">
                        <div class="symbol symbol-90px mb-6">
                            <div class="symbol-label bg-light-primary rounded-circle">
                                <i class="bi bi-people text-primary fs-1"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-gray-900 mb-2">
                            No staff members found
                        </h3>
                        <div class="text-muted fs-6 mb-7">
                            Try adjusting your filters or add your first team member.
                        </div>
                        @can('create', \App\Models\Staff::class)
                            <a href="{{ route('staff-management.staff.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-person-plus-fill me-2"></i>
                                Add Staff Member
                            </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('staffFilterForm');
                const searchInput = document.querySelector('[data-staff-realtime-search]');
                const spinner = document.getElementById('staff-search-spinner');

                if (!form || !searchInput) {
                    return;
                }

                let searchTimer;

                const submitSearch = () => {
                    if (searchInput.value === searchInput.dataset.originalValue) {
                        return;
                    }

                    spinner?.classList.remove('d-none');
                    form.requestSubmit();
                };

                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(submitSearch, 450);
                });

                searchInput.addEventListener('search', () => {
                    clearTimeout(searchTimer);
                    submitSearch();
                });
            });
        </script>
    @endpush

    @include('pages.apps.staff-management.staff._sweet-alerts')

</x-default-layout>
