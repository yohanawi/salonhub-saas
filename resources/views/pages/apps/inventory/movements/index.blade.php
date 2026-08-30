<x-default-layout>

    @section('title')
        Stock Movements
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.movements.index') }}
    @endsection

    @php
        $activeFilterCount = collect(['tenant_id', 'branch_id', 'product_id', 'type', 'date_from', 'date_to'])
            ->filter(fn(string $filter) => request()->filled($filter))
            ->count();
        $hasActiveFilters = request()->filled('search') || $activeFilterCount > 0;
    @endphp


    <div id="kt_app_content_container">

        {{-- ========================================================= --}}
        {{-- ALERTS --}}
        {{-- ========================================================= --}}
        @include('pages/apps.inventory.partials._alerts')


        {{-- ========================================================= --}}
        {{-- HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-5">

            <div class="card-body p-4 p-lg-6">

                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-7">

                    <div class="d-flex align-items-start">

                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-arrow-left-right text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Stock Movements
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($movements->total()) }}
                                    {{ Str::plural('Movement', $movements->total()) }}
                                </span>

                                <span class="badge badge-light-info px-3 py-2">
                                    Inventory Audit Trail
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Review every inventory increase, decrease and stock balance
                                change across your branches.
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- LEDGER --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8 flex-wrap gap-3">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-journal-text text-info fs-3"></i>
                        </div>
                    </div>

                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Stock Movement Ledger
                        </h3>

                        <div class="text-muted fs-8">
                            Chronological record of inventory changes and their sources.
                        </div>

                    </div>

                </div>


                <div class="card-toolbar">
                    <form id="movementFilterForm" method="GET" action="{{ route('inventory.movements.index') }}"
                        class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                        <span class="badge badge-light-info px-3 py-2">
                            {{ number_format($movements->total()) }}
                            Records
                        </span>

                        <div class="position-relative">
                            <i
                                class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted fs-5"></i>
                            <input type="search" name="search" id="movement-search-input"
                                value="{{ request('search') }}" autocomplete="off"
                                class="form-control form-control-solid ps-12 pe-12 w-225px w-md-300px"
                                placeholder="Search movements..." data-movement-realtime-search
                                data-original-value="{{ request('search') }}">
                            <span id="movement-search-spinner"
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
                                    Filter Movements
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
                                            Product
                                        </label>
                                        <select name="product_id" class="form-select form-select-sm"
                                            data-control="select2">
                                            <option value="">
                                                All products
                                            </option>

                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Movement Type
                                        </label>
                                        <select name="type" class="form-select form-select-sm" data-control="select2"
                                            data-hide-search="true">
                                            <option value="">
                                                All types
                                            </option>

                                            @foreach ($types as $type)
                                                <option value="{{ $type }}" @selected(request('type') === $type)>
                                                    {{ str($type)->headline() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Date From
                                        </label>
                                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                                            class="form-control form-control-sm">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Date To
                                        </label>
                                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                                            class="form-control form-control-sm">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-6">
                                    <a href="{{ route('inventory.movements.index') }}" class="btn btn-sm btn-light">
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

            @if ($hasActiveFilters)
                <div class="px-9 pb-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="text-muted fs-8 fw-semibold me-1">
                                Active:
                            </span>

                            @if (request()->filled('search'))
                                <span class="badge badge-light-dark px-3 py-2">
                                    <i class="bi bi-search me-1"></i>
                                    {{ request('search') }}
                                </span>
                            @endif

                            @if (($isSuperAdmin ?? false) && request()->filled('tenant_id'))
                                @php
                                    $selectedTenant = $tenants->firstWhere('id', (int) request('tenant_id'));
                                @endphp

                                @if ($selectedTenant)
                                    <span class="badge badge-light-primary px-3 py-2">
                                        <i class="bi bi-shop me-1"></i>
                                        {{ $selectedTenant->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('branch_id'))
                                @php
                                    $selectedBranch = $branches->firstWhere('id', (int) request('branch_id'));
                                @endphp

                                @if ($selectedBranch)
                                    <span class="badge badge-light-info px-3 py-2">
                                        <i class="bi bi-geo-alt-fill me-1"></i>
                                        {{ $selectedBranch->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('product_id'))
                                @php
                                    $selectedProduct = $products->firstWhere('id', (int) request('product_id'));
                                @endphp

                                @if ($selectedProduct)
                                    <span class="badge badge-light-primary px-3 py-2">
                                        <i class="bi bi-box-seam me-1"></i>
                                        {{ $selectedProduct->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('type'))
                                <span class="badge badge-light-warning px-3 py-2">
                                    <i class="bi bi-arrow-left-right me-1"></i>
                                    {{ str(request('type'))->headline() }}
                                </span>
                            @endif

                            @if (request()->filled('date_from'))
                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    From {{ request('date_from') }}
                                </span>
                            @endif

                            @if (request()->filled('date_to'))
                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    To {{ request('date_to') }}
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('inventory.movements.index') }}" class="btn btn-sm btn-light">
                            <i class="bi bi-x-circle me-2"></i>
                            Clear All
                        </a>
                    </div>
                </div>
            @endif


            <div class="card-body pt-4">

                @if ($movements->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-160px">
                                        Date & Time
                                    </th>

                                    <th class="min-w-230px">
                                        Product
                                    </th>

                                    <th class="min-w-160px">
                                        Branch
                                    </th>

                                    <th class="min-w-140px">
                                        Movement
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Quantity
                                    </th>

                                    <th class="text-end min-w-90px">
                                        Before
                                    </th>

                                    <th class="text-end min-w-90px">
                                        After
                                    </th>

                                    <th class="min-w-150px">
                                        Reference
                                    </th>

                                    <th class="min-w-150px">
                                        User
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($movements as $movement)
                                    @php
                                        $isIncoming = $movement->quantity >= 0;

                                        $movementClass = $isIncoming ? 'success' : 'danger';

                                        $movementIcon = $isIncoming ? 'bi-arrow-down-left' : 'bi-arrow-up-right';

                                        $referenceName = $movement->reference_type
                                            ? class_basename($movement->reference_type)
                                            : null;
                                    @endphp


                                    <tr>

                                        {{-- Date --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-40px me-3 flex-shrink-0">
                                                    <div class="symbol-label bg-light">
                                                        <i class="bi bi-clock-history text-muted"></i>
                                                    </div>
                                                </div>

                                                <div>

                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $movement->created_at?->format('M d, Y') }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        {{ $movement->created_at?->format('h:i A') }}
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Product --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-45px me-4 flex-shrink-0">
                                                    <div class="symbol-label bg-light-primary rounded-3">
                                                        <i class="bi bi-box-seam-fill text-primary"></i>
                                                    </div>
                                                </div>

                                                <div>

                                                    <div class="fw-bold text-gray-900">
                                                        {{ $movement->product?->name ?? 'Unknown Product' }}
                                                    </div>

                                                    @if ($movement->product?->sku)
                                                        <div class="text-muted fs-8">
                                                            <i class="bi bi-upc-scan me-1"></i>
                                                            SKU {{ $movement->product->sku }}
                                                        </div>
                                                    @endif

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Branch --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light-info">
                                                        <i class="bi bi-geo-alt-fill text-info"></i>
                                                    </div>
                                                </div>

                                                <span class="fw-semibold text-gray-900">
                                                    {{ $movement->branch?->name ?? '—' }}
                                                </span>

                                            </div>

                                        </td>


                                        {{-- Type --}}
                                        <td>

                                            <span class="badge badge-light-{{ $movementClass }} px-3 py-2">

                                                <i class="bi {{ $movementIcon }} me-1"></i>

                                                {{ $movement->type_label }}

                                            </span>

                                        </td>


                                        {{-- Quantity --}}
                                        <td class="text-end">

                                            <div class="fw-bolder text-{{ $movementClass }} fs-6">

                                                <i class="bi {{ $movementIcon }} me-1"></i>

                                                {{ $movement->quantity > 0 ? '+' : '' }}
                                                {{ number_format($movement->quantity) }}

                                            </div>

                                            <div class="text-muted fs-8">
                                                units
                                            </div>

                                        </td>


                                        {{-- Before --}}
                                        <td class="text-end">

                                            <span class="badge badge-light px-3 py-2">
                                                {{ number_format($movement->quantity_before) }}
                                            </span>

                                        </td>


                                        {{-- After --}}
                                        <td class="text-end">

                                            <span class="badge badge-light-primary px-3 py-2">
                                                {{ number_format($movement->quantity_after) }}
                                            </span>

                                        </td>


                                        {{-- Reference --}}
                                        <td>

                                            @if ($referenceName || $movement->reference_id)
                                                <div class="d-flex align-items-center">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-warning">
                                                            <i class="bi bi-link-45deg text-warning"></i>
                                                        </div>
                                                    </div>

                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            {{ $referenceName ?? 'Reference' }}
                                                        </div>

                                                        <div class="text-muted fs-8">
                                                            #{{ $movement->reference_id ?? '—' }}
                                                        </div>

                                                    </div>

                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endif

                                        </td>


                                        {{-- User --}}
                                        <td>

                                            @if ($movement->creator)
                                                <div class="d-flex align-items-center">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-success">
                                                            <i class="bi bi-person-fill text-success"></i>
                                                        </div>
                                                    </div>

                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            {{ $movement->creator->name }}
                                                        </div>

                                                        <div class="text-muted fs-8">
                                                            Recorded By
                                                        </div>

                                                    </div>

                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    System / Unknown
                                                </span>
                                            @endif

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- ================================================= --}}
                    {{-- PAGINATION --}}
                    {{-- ================================================= --}}
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 border-top border-gray-200 pt-6 mt-6">

                        <div class="text-muted fs-7">

                            Showing

                            <span class="fw-bold text-gray-800">
                                {{ $movements->firstItem() ?? 0 }}
                            </span>

                            to

                            <span class="fw-bold text-gray-800">
                                {{ $movements->lastItem() ?? 0 }}
                            </span>

                            of

                            <span class="fw-bold text-gray-800">
                                {{ number_format($movements->total()) }}
                            </span>

                            movement records

                        </div>


                        @if ($movements->hasPages())
                            <div>
                                {{ $movements->withQueryString()->links() }}
                            </div>
                        @endif

                    </div>
                @else
                    {{-- ================================================= --}}
                    {{-- EMPTY STATE --}}
                    {{-- ================================================= --}}
                    <div class="text-center py-15">

                        <div class="symbol symbol-90px mb-6">

                            <div class="symbol-label bg-light-primary rounded-circle">
                                <i class="bi bi-arrow-left-right text-primary fs-1"></i>
                            </div>

                        </div>


                        <h3 class="fw-bold text-gray-900 mb-3">
                            No Stock Movements Found
                        </h3>


                        <div class="text-muted fs-7 mw-500px mx-auto mb-7">

                            @if ($hasActiveFilters)
                                No stock movement records matched your current filters.
                                Try adjusting the branch, product, type or date range.
                            @else
                                Inventory movements will appear here whenever stock
                                is received, adjusted, sold, transferred or otherwise changed.
                            @endif

                        </div>


                        @if ($hasActiveFilters)
                            <a href="{{ route('inventory.movements.index') }}" class="btn btn-light-primary">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                Clear Filters
                            </a>
                        @endif

                    </div>

                @endif

            </div>

        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('movementFilterForm');
                const searchInput = document.querySelector('[data-movement-realtime-search]');
                const spinner = document.getElementById('movement-search-spinner');

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

</x-default-layout>
