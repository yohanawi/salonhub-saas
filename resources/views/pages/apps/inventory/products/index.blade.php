<x-default-layout>

    @section('title')
        Products
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.index') }}
    @endsection

    @php
        $activeFilterCount = collect(['tenant_id', 'category_id', 'brand_id', 'product_type', 'status'])
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
                                <i class="bi bi-box-seam-fill text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Products
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($products->total()) }}
                                    {{ Str::plural('Product', $products->total()) }}
                                </span>

                                <span class="badge badge-light-info px-3 py-2">
                                    Inventory Catalog
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Manage retail and consumable products, stock levels,
                                pricing, brands and product classifications.
                            </div>

                        </div>

                    </div>


                    @can('create', \App\Models\Product::class)
                        <a href="{{ route('inventory.products.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Add Product
                        </a>
                    @endcan

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- PRODUCT DIRECTORY --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8 flex-wrap gap-3">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-boxes text-primary fs-3"></i>
                        </div>
                    </div>

                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Product Directory
                        </h3>

                        <div class="text-muted fs-8">
                            {{ number_format($products->total()) }}
                            {{ Str::plural('product', $products->total()) }}
                            found
                        </div>

                    </div>

                </div>


                <div class="card-toolbar">
                    <form id="productFilterForm" method="GET" action="{{ route('inventory.products.index') }}"
                        class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                        <div class="position-relative">
                            <i
                                class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted fs-5"></i>
                            <input type="search" name="search" id="product-search-input"
                                value="{{ request('search') }}" autocomplete="off"
                                class="form-control form-control-solid ps-12 pe-12 w-225px w-md-300px"
                                placeholder="Search products..." data-product-realtime-search
                                data-original-value="{{ request('search') }}">
                            <span id="product-search-spinner"
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
                                    Filter Products
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

                                            @foreach ($productTypes as $type)
                                                <option value="{{ $type }}" @selected(request('product_type') === $type)>
                                                    {{ str($type)->headline() }}
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
                                            <option value="active" @selected(request('status') === 'active')>
                                                Active
                                            </option>
                                            <option value="inactive" @selected(request('status') === 'inactive')>
                                                Inactive
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-6">
                                    <a href="{{ route('inventory.products.index') }}" class="btn btn-sm btn-light">
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
                                <span class="badge badge-light-primary px-3 py-2">
                                    <i class="bi bi-search me-1"></i>
                                    {{ request('search') }}
                                </span>
                            @endif

                            @if (request()->filled('tenant_id'))
                                @php
                                    $selectedTenant = $tenants->firstWhere('id', (int) request('tenant_id'));
                                @endphp

                                @if ($selectedTenant)
                                    <span class="badge badge-light-info px-3 py-2">
                                        <i class="bi bi-shop me-1"></i>
                                        {{ $selectedTenant->name }}
                                    </span>
                                @endif
                            @endif

                            @if (request()->filled('category_id'))
                                @php
                                    $selectedCategory = $categories->firstWhere('id', (int) request('category_id'));
                                @endphp

                                @if ($selectedCategory)
                                    <span class="badge badge-light-success px-3 py-2">
                                        <i class="bi bi-grid me-1"></i>
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

                            @if (request()->filled('status'))
                                <span class="badge badge-light-{{ request('status') === 'active' ? 'success' : 'danger' }} px-3 py-2">
                                    {{ str(request('status'))->headline() }}
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('inventory.products.index') }}" class="btn btn-sm btn-light">
                            <i class="bi bi-x-circle me-2"></i>
                            Clear All
                        </a>
                    </div>
                </div>
            @endif


            <div class="card-body pt-4">

                @if ($products->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-260px">
                                        Product
                                    </th>

                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-160px">
                                            Salon
                                        </th>
                                    @endif

                                    <th class="min-w-140px">
                                        Category
                                    </th>

                                    <th class="min-w-130px">
                                        Brand
                                    </th>

                                    <th class="min-w-120px">
                                        Type
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Stock
                                    </th>

                                    <th class="text-end min-w-130px">
                                        Cost
                                    </th>

                                    <th class="text-end min-w-130px">
                                        Selling
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

                                @foreach ($products as $product)
                                    @php
                                        $stockQuantity = $product->inventories->sum('quantity_on_hand');

                                        $stockClass = match (true) {
                                            $stockQuantity <= 0 => 'danger',
                                            $stockQuantity <= ($product->reorder_level ?? 0) => 'warning',
                                            default => 'success',
                                        };

                                        $stockLabel = match (true) {
                                            $stockQuantity <= 0 => 'Out',
                                            $stockQuantity <= ($product->reorder_level ?? 0) => 'Low',
                                            default => 'Healthy',
                                        };
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


                                                <div class="d-flex flex-column">

                                                    <a href="{{ route('inventory.products.show', $product) }}"
                                                        class="text-gray-900 text-hover-primary fw-bold fs-6 mb-1">
                                                        {{ $product->name }}
                                                    </a>


                                                    <div
                                                        class="d-flex flex-wrap align-items-center gap-2 text-muted fs-8">

                                                        <span>
                                                            <i class="bi bi-upc-scan me-1"></i>
                                                            SKU {{ $product->sku }}
                                                        </span>


                                                        @if ($product->barcode)
                                                            <span>
                                                                · {{ $product->barcode }}
                                                            </span>
                                                        @endif

                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>

                                                @if ($product->tenant)
                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="bi bi-shop text-info"></i>
                                                            </div>
                                                        </div>

                                                        <span class="text-gray-900">
                                                            {{ $product->tenant->name }}
                                                        </span>

                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif

                                            </td>
                                        @endif


                                        {{-- Category --}}
                                        <td>

                                            @if ($product->category)
                                                <span class="badge badge-light-primary px-3 py-2">
                                                    {{ $product->category->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Brand --}}
                                        <td>

                                            @if ($product->brand)
                                                <div class="fw-semibold text-gray-900">
                                                    {{ $product->brand->name }}
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Type --}}
                                        <td>

                                            <span class="badge badge-light-info px-3 py-2">
                                                {{ $product->product_type_label }}
                                            </span>

                                        </td>


                                        {{-- Stock --}}
                                        <td class="text-end">

                                            <div class="d-flex flex-column align-items-end">

                                                <div class="fw-bolder text-gray-900 fs-6">
                                                    {{ number_format($stockQuantity) }}
                                                </div>

                                                <span class="badge badge-light-{{ $stockClass }} mt-1">
                                                    {{ $stockLabel }}
                                                </span>

                                            </div>

                                        </td>


                                        {{-- Cost --}}
                                        <td class="text-end">

                                            <div class="fw-semibold text-gray-800">
                                                LKR {{ number_format((float) $product->cost_price, 2) }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Cost
                                            </div>

                                        </td>


                                        {{-- Selling --}}
                                        <td class="text-end">

                                            <div class="fw-bold text-gray-900">
                                                LKR {{ number_format((float) $product->selling_price, 2) }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Retail
                                            </div>

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            <span
                                                class="badge badge-light-{{ $product->is_active ? 'success' : 'danger' }} px-3 py-2">

                                                <i
                                                    class="bi {{ $product->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }} me-1"></i>

                                                {{ $product->is_active ? 'Active' : 'Inactive' }}

                                            </span>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <a href="{{ route('inventory.products.show', $product) }}"
                                                class="btn btn-sm btn-icon btn-light-primary me-1"
                                                data-bs-toggle="tooltip" title="View Product">
                                                <i class="bi bi-eye"></i>
                                            </a>


                                            @can('update', $product)
                                                <a href="{{ route('inventory.products.edit', $product) }}"
                                                    class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip"
                                                    title="Edit Product">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endcan

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
                                {{ $products->firstItem() ?? 0 }}
                            </span>

                            to

                            <span class="fw-bold text-gray-800">
                                {{ $products->lastItem() ?? 0 }}
                            </span>

                            of

                            <span class="fw-bold text-gray-800">
                                {{ number_format($products->total()) }}
                            </span>

                            products

                        </div>


                        @if ($products->hasPages())
                            <div>
                                {{ $products->withQueryString()->links() }}
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
                                <i class="bi bi-box-seam text-primary fs-1"></i>
                            </div>

                        </div>


                        <h3 class="fw-bold text-gray-900 mb-3">
                            No Products Found
                        </h3>


                        <div class="text-muted fs-7 mw-500px mx-auto mb-7">

                            @if ($hasActiveFilters)
                                No products match your current search or filter criteria.
                                Try changing or clearing the active filters.
                            @else
                                Your inventory catalog is currently empty.
                                Add your first product to begin stock tracking.
                            @endif

                        </div>


                        <div class="d-flex flex-wrap justify-content-center gap-3">

                            @if ($hasActiveFilters)
                                <a href="{{ route('inventory.products.index') }}" class="btn btn-light">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                                    Clear Filters
                                </a>
                            @endif


                            @can('create', \App\Models\Product::class)
                                <a href="{{ route('inventory.products.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle-fill me-2"></i>
                                    Add Product
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
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('productFilterForm');
                const searchInput = document.querySelector('[data-product-realtime-search]');
                const spinner = document.getElementById('product-search-spinner');

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
