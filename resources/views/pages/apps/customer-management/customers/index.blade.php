<x-default-layout>

    @section('title')
        Customers
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('customer-management.customers.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">
                <div class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-success bg-opacity-10">
                        <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    </span>
                </div>

                <div>
                    <div class="fw-bold text-gray-900">Success</div>
                    <div class="text-gray-700">{{ session('status') }}</div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-8">
                <div class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-danger bg-opacity-10">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    </span>
                </div>

                <div>
                    <div class="fw-bold text-gray-900">Something went wrong</div>
                    <div class="text-gray-700">{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm overflow-hidden mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-6">
                    <div class="d-flex align-items-center gap-5">
                        <div class="symbol symbol-60px symbol-lg-70px">
                            <span class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-people-fill text-primary fs-1"></i>
                            </span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <h1 class="fw-bolder text-gray-900 mb-0">
                                    Customers
                                </h1>
                                <span class="badge badge-light-primary fw-bold">
                                    {{ number_format($customers->total()) }}
                                </span>
                            </div>
                            <div class="text-muted mt-2">
                                Manage customer profiles, contact information,
                                salon preferences and visit activity.
                            </div>
                        </div>
                    </div>
                    @can('create', \App\Models\Customer::class)
                        <a href="{{ route('customer-management.customers.create') }}" class="btn btn-primary px-6">
                            <i class="bi bi-person-plus-fill fs-5 me-2"></i>
                            Add Customer
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-primary rounded-3">
                                <i class="bi bi-people text-primary fs-2"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold fs-7 mb-1">
                                Customers Found
                            </div>
                            <div class="fw-bolder text-gray-900 fs-2">
                                {{ number_format($customers->total()) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-success rounded-3">
                                <i class="bi bi-person-check-fill text-success fs-2"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold fs-7 mb-1">
                                Current View
                            </div>
                            <div class="fw-bolder text-gray-900 fs-4">
                                {{ request('status') ? str(request('status'))->headline() : 'All Customers' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-info rounded-3">
                                <i class="bi bi-shop text-info fs-2"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold fs-7 mb-1">
                                Branch Filter
                            </div>
                            <div class="fw-bolder text-gray-900 fs-4">
                                @php
                                    $selectedBranch = $branches->firstWhere('id', request('branch_id'));
                                @endphp
                                {{ $selectedBranch?->name ?? 'All Branches' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Find Customers
                        </h3>
                        <div class="text-muted fs-7">
                            Search and filter your customer directory.
                        </div>
                    </div>
                </div>

                @if (request()->filled('search') ||
                        request()->filled('branch_id') ||
                        request()->filled('status') ||
                        request()->filled('gender') ||
                        request()->filled('tenant_id'))
                    <div class="card-toolbar">
                        <span class="badge badge-light-primary">
                            <i class="bi bi-funnel-fill me-1"></i>
                            Filters Active
                        </span>
                    </div>
                @endif
            </div>

            <div class="card-body pt-5">
                <form method="GET" action="{{ route('customer-management.customers.index') }}">
                    <div class="row g-5">
                        <div class="col-xl-4 col-lg-6">
                            <label class="form-label fw-semibold">
                                Search Customer
                            </label>
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                                <input type="search" name="search" value="{{ request('search') }}"
                                    class="form-control form-control-solid ps-12"
                                    placeholder="Name, phone, email or customer code...">
                            </div>
                        </div>

                        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                            <div class="col-xl-2 col-lg-6 col-md-6">
                                <label class="form-label fw-semibold">
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
                            <label class="form-label fw-semibold">
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


                        {{-- Status --}}
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label fw-semibold">
                                Status
                            </label>

                            <select name="status" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All statuses</option>

                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>
                                        {{ str($status)->headline() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        {{-- Gender --}}
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label fw-semibold">
                                Gender
                            </label>

                            <select name="gender" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All genders</option>

                                @foreach ($genders as $gender)
                                    <option value="{{ $gender }}" @selected(request('gender') === $gender)>
                                        {{ str($gender)->headline() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>


                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-3 mt-6">
                        <a href="{{ route('customer-management.customers.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Reset
                        </a>

                        <button type="submit" class="btn btn-primary px-6">
                            <i class="bi bi-funnel-fill me-2"></i>
                            Apply Filters
                        </button>
                    </div>

                </form>

            </div>
        </div>


        {{-- Customer Directory --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 py-6">

                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Customer Directory
                        </h3>

                        <div class="text-muted fs-7">
                            Showing
                            {{ $customers->firstItem() ?? 0 }}
                            –
                            {{ $customers->lastItem() ?? 0 }}
                            of
                            {{ number_format($customers->total()) }}
                            customers
                        </div>
                    </div>
                </div>

            </div>


            <div class="card-body pt-0">

                @if ($customers->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-5">

                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                    <th class="min-w-250px">
                                        Customer
                                    </th>

                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-160px">
                                            Salon
                                        </th>
                                    @endif

                                    <th class="min-w-180px">
                                        Contact
                                    </th>

                                    <th class="min-w-150px">
                                        Branch
                                    </th>

                                    <th class="min-w-140px">
                                        Last Visit
                                    </th>

                                    <th class="min-w-110px">
                                        Status
                                    </th>

                                    <th class="text-end min-w-120px">
                                        Actions
                                    </th>
                                </tr>
                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($customers as $customer)
                                    @php
                                        $initials = collect([$customer->first_name, $customer->last_name])
                                            ->filter()
                                            ->map(fn($name) => mb_strtoupper(mb_substr($name, 0, 1)))
                                            ->join('');

                                        $statusClass = match ($customer->status) {
                                            'active' => 'success',
                                            'blocked' => 'danger',
                                            'inactive' => 'warning',
                                            default => 'secondary',
                                        };

                                        $statusIcon = match ($customer->status) {
                                            'active' => 'bi-check-circle-fill',
                                            'blocked' => 'bi-slash-circle-fill',
                                            'inactive' => 'bi-pause-circle-fill',
                                            default => 'bi-circle-fill',
                                        };
                                    @endphp

                                    <tr>

                                        {{-- Customer --}}
                                        <td>
                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-50px symbol-circle me-4">
                                                    <span
                                                        class="symbol-label bg-light-primary text-primary fw-bold fs-5">
                                                        {{ $initials ?: '?' }}
                                                    </span>
                                                </div>

                                                <div class="d-flex flex-column">

                                                    <a href="{{ route('customer-management.customers.show', $customer) }}"
                                                        class="text-gray-900 text-hover-primary fw-bold fs-6 mb-1">
                                                        {{ $customer->full_name }}
                                                    </a>

                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge badge-light fs-8">
                                                            <i class="bi bi-upc-scan me-1"></i>

                                                            {{ $customer->customer_code ?: 'No Code' }}
                                                        </span>

                                                        @if ($customer->gender)
                                                            <span class="text-muted fs-8">
                                                                {{ str($customer->gender)->headline() }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                </div>

                                            </div>
                                        </td>


                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="symbol symbol-35px me-3">
                                                        <span class="symbol-label bg-light-info">
                                                            <i class="bi bi-shop text-info"></i>
                                                        </span>
                                                    </div>

                                                    <span class="text-gray-800">
                                                        {{ $customer->tenant?->name ?? '-' }}
                                                    </span>
                                                </div>
                                            </td>
                                        @endif


                                        {{-- Contact --}}
                                        <td>
                                            <div class="d-flex flex-column gap-1">

                                                @if ($customer->phone)
                                                    <a href="tel:{{ $customer->phone }}"
                                                        class="text-gray-800 text-hover-primary">
                                                        <i class="bi bi-telephone me-2 text-muted"></i>

                                                        {{ $customer->phone }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">
                                                        No phone
                                                    </span>
                                                @endif


                                                @if ($customer->email)
                                                    <a href="mailto:{{ $customer->email }}"
                                                        class="text-muted text-hover-primary fs-8">
                                                        <i class="bi bi-envelope me-2"></i>

                                                        {{ Str::limit($customer->email, 28) }}
                                                    </a>
                                                @endif

                                            </div>
                                        </td>


                                        {{-- Branch --}}
                                        <td>
                                            @if ($customer->branch)
                                                <span class="badge badge-light-info">
                                                    <i class="bi bi-geo-alt-fill me-1"></i>

                                                    {{ $customer->branch->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    Not assigned
                                                </span>
                                            @endif
                                        </td>


                                        {{-- Last Visit --}}
                                        <td>
                                            @if ($customer->last_visit_at)
                                                <div class="fw-semibold text-gray-800">
                                                    {{ $customer->last_visit_at->format('M d, Y') }}
                                                </div>

                                                <div class="text-muted fs-8">
                                                    {{ $customer->last_visit_at->diffForHumans() }}
                                                </div>
                                            @else
                                                <div class="text-muted">
                                                    <i class="bi bi-calendar-x me-1"></i>
                                                    No visits yet
                                                </div>
                                            @endif
                                        </td>


                                        {{-- Status --}}
                                        <td>
                                            <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                                <i class="bi {{ $statusIcon }} me-1"></i>

                                                {{ $customer->status_label }}
                                            </span>
                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                                <i class="bi bi-three-dots-vertical fs-5"></i>
                                            </button>


                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-3 w-200px"
                                                data-kt-menu="true">

                                                <div class="menu-item px-3">
                                                    <div class="menu-content text-muted pb-2 px-3 fs-8 text-uppercase">
                                                        Customer Actions
                                                    </div>
                                                </div>


                                                <div class="menu-item px-3">
                                                    <a href="{{ route('customer-management.customers.show', $customer) }}"
                                                        class="menu-link px-3">
                                                        <i class="bi bi-person-vcard me-3"></i>
                                                        View Profile
                                                    </a>
                                                </div>


                                                @can('update', $customer)
                                                    <div class="menu-item px-3">
                                                        <a href="{{ route('customer-management.customers.edit', $customer) }}"
                                                            class="menu-link px-3">
                                                            <i class="bi bi-pencil-square me-3"></i>
                                                            Edit Customer
                                                        </a>
                                                    </div>
                                                @endcan


                                                @if ($customer->phone)
                                                    <div class="menu-item px-3">
                                                        <a href="tel:{{ $customer->phone }}" class="menu-link px-3">
                                                            <i class="bi bi-telephone me-3"></i>
                                                            Call Customer
                                                        </a>
                                                    </div>
                                                @endif


                                                @if ($customer->email)
                                                    <div class="menu-item px-3">
                                                        <a href="mailto:{{ $customer->email }}"
                                                            class="menu-link px-3">
                                                            <i class="bi bi-envelope me-3"></i>
                                                            Send Email
                                                        </a>
                                                    </div>
                                                @endif

                                            </div>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- Pagination --}}
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mt-8">
                        <div class="text-muted fs-7">
                            Page {{ $customers->currentPage() }}
                            of {{ $customers->lastPage() }}
                        </div>

                        <div>
                            {{ $customers->links() }}
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-15">

                        <div class="mb-6">
                            <div class="symbol symbol-100px mx-auto">
                                <span class="symbol-label bg-light-primary rounded-circle">
                                    <i class="bi bi-person-heart text-primary" style="font-size: 3rem;"></i>
                                </span>
                            </div>
                        </div>

                        <h3 class="fw-bold text-gray-900 mb-3">
                            No customers found
                        </h3>

                        <div class="text-muted mw-500px mx-auto mb-7">
                            @if (request()->filled('search') ||
                                    request()->filled('branch_id') ||
                                    request()->filled('status') ||
                                    request()->filled('gender'))
                                No customers match your current search or
                                filter criteria. Try adjusting the filters.
                            @else
                                Your customer directory is currently empty.
                                Add your first customer to start building
                                customer profiles and visit history.
                            @endif
                        </div>

                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            @if (request()->filled('search') ||
                                    request()->filled('branch_id') ||
                                    request()->filled('status') ||
                                    request()->filled('gender'))
                                <a href="{{ route('customer-management.customers.index') }}" class="btn btn-light">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                                    Clear Filters
                                </a>
                            @endif


                            @can('create', \App\Models\Customer::class)
                                <a href="{{ route('customer-management.customers.create') }}" class="btn btn-primary">
                                    <i class="bi bi-person-plus-fill me-2"></i>
                                    Add Customer
                                </a>
                            @endcan
                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-default-layout>
