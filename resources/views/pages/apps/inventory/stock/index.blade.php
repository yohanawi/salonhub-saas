<x-default-layout>

    @section('title')
        Current Stock
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.stock.index') }}
    @endsection

    @php
        $activeFilterCount = collect(['tenant_id', 'branch_id', 'stock_status', 'category_id', 'brand_id', 'product_type'])
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

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-clipboard-data-fill text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Current Stock
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($stocks->total()) }}
                                    {{ Str::plural('Record', $stocks->total()) }}
                                </span>

                                <span class="badge badge-light-info px-3 py-2">
                                    Live Inventory
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Monitor on-hand, reserved and available inventory quantities
                                across every branch.
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- STOCK DIRECTORY --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8 flex-wrap gap-3">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-boxes text-info fs-3"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Stock Levels
                        </h3>

                        <div class="text-muted fs-8">
                            Current inventory balances and stock health by branch.
                        </div>

                    </div>

                </div>


                <div class="card-toolbar">
                    <form id="stockFilterForm" method="GET" action="{{ route('inventory.stock.index') }}"
                        class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                        <span class="badge badge-light-info px-3 py-2">
                            {{ number_format($stocks->total()) }}
                            {{ Str::plural('Stock Record', $stocks->total()) }}
                        </span>

                        <div class="position-relative">
                            <i
                                class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted fs-5"></i>
                            <input type="search" name="search" id="stock-search-input"
                                value="{{ request('search') }}" autocomplete="off"
                                class="form-control form-control-solid ps-12 pe-12 w-225px w-md-300px"
                                placeholder="Search stock..." data-stock-realtime-search
                                data-original-value="{{ request('search') }}">
                            <span id="stock-search-spinner"
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
                                    Filter Stock
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
                                            Stock Status
                                        </label>
                                        <select name="stock_status" class="form-select form-select-sm"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                All statuses
                                            </option>

                                            @foreach (['in_stock', 'low_stock', 'out_of_stock'] as $status)
                                                <option value="{{ $status }}" @selected(request('stock_status') === $status)>
                                                    {{ str($status)->headline() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Category
                                        </label>
                                        <select name="category_id" class="form-select form-select-sm"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                All categories
                                            </option>

                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Brand
                                        </label>
                                        <select name="brand_id" class="form-select form-select-sm"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                All brands
                                            </option>

                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>
                                                    {{ $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Product Type
                                        </label>
                                        <select name="product_type" class="form-select form-select-sm"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                All types
                                            </option>

                                            @foreach (\App\Models\Product::TYPES as $type)
                                                <option value="{{ $type }}" @selected(request('product_type') === $type)>
                                                    {{ str($type)->headline() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-6">
                                    <a href="{{ route('inventory.stock.index') }}" class="btn btn-sm btn-light">
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
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $selectedBranch->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('stock_status'))
                                <span class="badge badge-light-warning px-3 py-2">
                                    <i class="bi bi-boxes me-1"></i>
                                    {{ str(request('stock_status'))->headline() }}
                                </span>
                            @endif

                            @if (request()->filled('category_id'))
                                @php
                                    $selectedCategory = $categories->firstWhere('id', (int) request('category_id'));
                                @endphp

                                @if ($selectedCategory)
                                    <span class="badge badge-light-success px-3 py-2">
                                        <i class="bi bi-tags me-1"></i>
                                        {{ $selectedCategory->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('brand_id'))
                                @php
                                    $selectedBrand = $brands->firstWhere('id', (int) request('brand_id'));
                                @endphp

                                @if ($selectedBrand)
                                    <span class="badge badge-light-warning px-3 py-2">
                                        <i class="bi bi-award me-1"></i>
                                        {{ $selectedBrand->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('product_type'))
                                <span class="badge badge-light-dark px-3 py-2">
                                    {{ str(request('product_type'))->headline() }}
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('inventory.stock.index') }}" class="btn btn-sm btn-light">
                            <i class="bi bi-x-circle me-2"></i>
                            Clear All
                        </a>
                    </div>
                </div>
            @endif


            <div class="card-body pt-4">

                @if ($stocks->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-260px">
                                        Product
                                    </th>

                                    <th class="min-w-180px">
                                        Branch
                                    </th>

                                    <th class="min-w-140px">
                                        Category
                                    </th>

                                    <th class="text-end min-w-100px">
                                        On Hand
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Reserved
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Available
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Reorder
                                    </th>

                                    <th class="text-end min-w-150px">
                                        Stock Value
                                    </th>

                                    <th class="min-w-130px">
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($stocks as $stock)
                                    @php
                                        $stockClass = match ($stock->stock_status) {
                                            'in_stock' => 'success',
                                            'low_stock' => 'warning',
                                            default => 'danger',
                                        };

                                        $stockIcon = match ($stock->stock_status) {
                                            'in_stock' => 'bi-check-circle-fill',
                                            'low_stock' => 'bi-exclamation-triangle-fill',
                                            default => 'bi-x-octagon-fill',
                                        };

                                        $stockValue = (float) $stock->average_cost * (int) $stock->quantity_on_hand;
                                    @endphp


                                    <tr>

                                        {{-- Product --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-50px me-4 flex-shrink-0">

                                                    <div class="symbol-label bg-light-primary rounded-3">
                                                        <i class="bi bi-box-seam-fill text-primary fs-3"></i>
                                                    </div>

                                                </div>


                                                <div>

                                                    @if ($stock->product)
                                                        <a href="{{ route('inventory.products.show', $stock->product) }}"
                                                            class="fw-bold text-gray-900 text-hover-primary fs-6">
                                                            {{ $stock->product->name }}
                                                        </a>
                                                    @else
                                                        <span class="fw-bold text-gray-900">
                                                            Unknown Product
                                                        </span>
                                                    @endif


                                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">

                                                        <span class="text-muted fs-8">
                                                            <i class="bi bi-upc-scan me-1"></i>
                                                            SKU {{ $stock->product?->sku ?? '—' }}
                                                        </span>


                                                        @if ($stock->product?->barcode)
                                                            <span class="text-muted fs-8">
                                                                · {{ $stock->product->barcode }}
                                                            </span>
                                                        @endif

                                                    </div>

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


                                                <div>

                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $stock->branch?->name ?? 'Unknown Branch' }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        Stock Location
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Category --}}
                                        <td>

                                            @if ($stock->product?->category)
                                                <span class="badge badge-light-primary px-3 py-2">
                                                    {{ $stock->product->category->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endif

                                        </td>


                                        {{-- On Hand --}}
                                        <td class="text-end">

                                            <div class="fw-bolder text-gray-900">
                                                {{ number_format($stock->quantity_on_hand) }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                units
                                            </div>

                                        </td>


                                        {{-- Reserved --}}
                                        <td class="text-end">

                                            <span class="fw-bold text-warning">
                                                {{ number_format($stock->quantity_reserved) }}
                                            </span>

                                        </td>


                                        {{-- Available --}}
                                        <td class="text-end">

                                            <span class="fw-bolder text-success">
                                                {{ number_format($stock->available_quantity) }}
                                            </span>

                                        </td>


                                        {{-- Reorder Level --}}
                                        <td class="text-end">

                                            <div class="fw-semibold text-gray-900">
                                                {{ number_format($stock->product?->reorder_level ?? 0) }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                threshold
                                            </div>

                                        </td>


                                        {{-- Stock Value --}}
                                        <td class="text-end">

                                            <div class="fw-bolder text-gray-900">
                                                LKR {{ number_format($stockValue, 2) }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Avg. cost × stock
                                            </div>

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            <span class="badge badge-light-{{ $stockClass }} px-3 py-2">
                                                <i class="bi {{ $stockIcon }} me-1"></i>
                                                {{ $stock->stock_status_label }}
                                            </span>

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
                                {{ $stocks->firstItem() ?? 0 }}
                            </span>

                            to

                            <span class="fw-bold text-gray-800">
                                {{ $stocks->lastItem() ?? 0 }}
                            </span>

                            of

                            <span class="fw-bold text-gray-800">
                                {{ number_format($stocks->total()) }}
                            </span>

                            stock records

                        </div>


                        @if ($stocks->hasPages())
                            <div>
                                {{ $stocks->withQueryString()->links() }}
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
                                <i class="bi bi-clipboard-x-fill text-primary fs-1"></i>
                            </div>

                        </div>


                        <h3 class="fw-bold text-gray-900 mb-3">
                            No Stock Records Found
                        </h3>


                        <div class="text-muted fs-7 mw-500px mx-auto mb-7">

                            @if ($hasActiveFilters)
                                No stock records matched your current filters.
                                Try clearing some filters and search again.
                            @else
                                Branch inventory levels will appear here once
                                products have stock records.
                            @endif

                        </div>


                        @if ($hasActiveFilters)
                            <a href="{{ route('inventory.stock.index') }}" class="btn btn-light-primary">
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
                const form = document.getElementById('stockFilterForm');
                const searchInput = document.querySelector('[data-stock-realtime-search]');
                const spinner = document.getElementById('stock-search-spinner');

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
