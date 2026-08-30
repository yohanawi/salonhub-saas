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

        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-6">
                    <div class="d-flex align-items-center gap-5">
                        <div class="symbol symbol-40px symbol-lg-50px">
                            <span class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-people-fill text-primary fs-1"></i>
                            </span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Customers
                                </h3>
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
                        <a href="{{ route('customer-management.customers.create') }}" class="btn btn-primary px-6 btn-sm">
                            <i class="bi bi-person-plus-fill fs-5 me-2"></i>
                            Add Customer
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8"> 

            {{-- Customer Directory --}}
            <div class="card border-0 shadow-sm">
                <form method="GET" action="{{ route('customer-management.customers.index') }}"
                    id="customerFilterForm">
                    <div class="card-header border-0 py-6">
                        <div
                            class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between w-100 gap-5">
                            <div class="d-flex align-items-center gap-4">
                                <div class="symbol symbol-45px">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-people text-primary fs-3"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-gray-900 mb-1">
                                        Customer Directory
                                    </h3>
                                    <div class="text-muted fs-8">
                                        Search and filter customer records.
                                    </div>
                                </div>
                            </div>
                            {{-- Filters --}}
                            <div class="d-flex flex-column flex-md-row align-items-md-end gap-4">
                                <div class="min-w-300px">
                                    <label class="form-label fw-semibold text-gray-700 fs-8 mb-2">
                                        Search Customer
                                    </label>
                                    <div class="position-relative">
                                        <i
                                            class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted fs-5"></i>
                                        <input type="search" name="search" id="customerSearch"
                                            value="{{ request('search') }}"
                                            class="form-control form-control-solid ps-12 pe-12"
                                            placeholder="Name, phone, email or code..." autocomplete="off">
                                        <span id="searchLoading"
                                            class="position-absolute top-50 end-0 translate-middle-y me-4 d-none">
                                            <span class="spinner-border spinner-border-sm text-primary"
                                                role="status"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="min-w-175px">
                                    <label class="form-label fw-semibold text-gray-700 fs-8 mb-2">
                                        Status
                                    </label>
                                    <select name="status" id="customerStatus" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                        <option value="">
                                            All Statuses
                                        </option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                                {{ str($status)->headline() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if (request()->filled('search') || request()->filled('status'))
                                    <div>
                                        <a href="{{ route('customer-management.customers.index') }}"
                                            class="btn btn-light-danger" data-bs-toggle="tooltip" title="Clear filters">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Clear
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

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
                                                        <div
                                                            class="menu-content text-muted pb-2 px-3 fs-8 text-uppercase">
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
                                                            <a href="tel:{{ $customer->phone }}"
                                                                class="menu-link px-3">
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
                                Showing
                                {{ $customers->firstItem() ?? 0 }}
                                –
                                {{ $customers->lastItem() ?? 0 }}
                                of
                                {{ number_format($customers->total()) }}
                                customers
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
                                @if (request()->filled('search') || request()->filled('status'))
                                    No customers match your current search or
                                    filter criteria. Try adjusting the filters.
                                @else
                                    Your customer directory is currently empty.
                                    Add your first customer to start building
                                    customer profiles and visit history.
                                @endif
                            </div>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                @if (request()->filled('search') || request()->filled('status'))
                                    <a href="{{ route('customer-management.customers.index') }}"
                                        class="btn btn-light">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                                        Clear Filters
                                    </a>
                                @endif
                                @can('create', \App\Models\Customer::class)
                                    <a href="{{ route('customer-management.customers.create') }}"
                                        class="btn btn-primary">
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

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {

                    const form = document.getElementById('customerFilterForm');
                    const searchInput = document.getElementById('customerSearch');
                    const statusSelect = document.getElementById('customerStatus');
                    const loading = document.getElementById('searchLoading');

                    let searchTimer = null;

                    searchInput.addEventListener('input', function() {
                        clearTimeout(searchTimer);
                        if (loading) {
                            loading.classList.remove('d-none');
                        }
                        searchTimer = setTimeout(function() {
                            form.submit();
                        }, 400);
                    });
                    statusSelect.addEventListener('change', function() {
                        if (loading) {
                            loading.classList.remove('d-none');
                        }
                        form.submit();
                    });
                });
            </script>
        @endpush

    @include('pages.apps.customer-management.customers._sweet-alerts')
</x-default-layout>
