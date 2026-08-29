<x-default-layout>

    @section('title')
        Inventory Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.dashboard') }}
    @endsection


    <div id="kt_app_content_container">

        {{-- ========================================================= --}}
        {{-- ALERTS --}}
        {{-- ========================================================= --}}
        @include('pages/apps.inventory.partials._alerts')


        {{-- ========================================================= --}}
        {{-- HERO / PAGE HEADER --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-5">

            <div class="card-body p-4 p-lg-6">

                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-7">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-65px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-boxes text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Inventory Dashboard
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    Stock Operations
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Monitor product availability, stock value, reorder risks and
                                recent inventory movements across your salon.
                            </div>

                        </div>

                    </div>


                    <div class="d-flex flex-wrap gap-3">

                        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-sliders me-2"></i>
                            Stock Adjustments
                        </a>


                        <a href="{{ route('inventory.products.index') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-seam-fill me-2"></i>
                            Manage Products
                        </a>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SUPER ADMIN SALON FILTER --}}
        {{-- ========================================================= --}}
        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())

            <div class="card border-0 shadow-sm mb-5">

                <div class="card-header border-0 pt-8">

                    <div class="card-title">

                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-shop text-info fs-3"></i>
                            </div>
                        </div>

                        <div>

                            <h3 class="fw-bold text-gray-900 mb-1">
                                Inventory Scope
                            </h3>

                            <div class="text-muted fs-8">
                                View inventory information for a specific salon.
                            </div>

                        </div>

                    </div>


                    @if (request()->filled('tenant_id'))
                        <div class="card-toolbar">

                            <span class="badge badge-light-primary px-3 py-2">
                                <i class="bi bi-funnel-fill me-1"></i>
                                Salon Filter Active
                            </span>

                        </div>
                    @endif

                </div>


                <div class="card-body pt-4">

                    <form method="GET">

                        <div class="row g-5 align-items-end">

                            <div class="col-lg-5 col-md-6">

                                <select name="tenant_id" class="form-select form-select-solid" data-control="select2"
                                    data-hide-search="true">

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


                            <div class="col-lg-4 col-md-6">

                                <div class="d-flex gap-3">

                                    <a href="{{ route('inventory.dashboard') }}" class="btn btn-light">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                                        Reset
                                    </a>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel-fill me-2"></i>
                                        Apply Filter
                                    </button>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- INVENTORY HEALTH OVERVIEW --}}
        {{-- ========================================================= --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mb-5">

            <div>

                <h3 class="fw-bold text-gray-900 mb-1">
                    Inventory Health
                </h3>

                <div class="text-muted fs-8">
                    Key indicators for your current stock position.
                </div>

            </div>


            @if ($lowStockCount > 0 || $outOfStockCount > 0)
                <span class="badge badge-light-warning px-4 py-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    {{ number_format($lowStockCount + $outOfStockCount) }}
                    stock
                    {{ Str::plural('alert', $lowStockCount + $outOfStockCount) }}
                </span>
            @else
                <span class="badge badge-light-success px-4 py-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Stock Healthy
                </span>
            @endif

        </div>


        {{-- ========================================================= --}}
        {{-- STAT CARDS --}}
        {{-- ========================================================= --}}
        <div class="row g-5 mb-8">

            {{-- Total Products --}}
            <div class="col-md-6 col-xl">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-box-seam-fill text-primary fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-primary">
                                Catalog
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">
                            Total Products
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($totalProducts) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Active Products --}}
            <div class="col-md-6 col-xl">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-success">
                                Active
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">
                            Active Products
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($activeProducts) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Stock Value --}}
            <div class="col-md-6 col-xl">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-cash-stack text-info fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-info">
                                Value
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">
                            Inventory Value
                        </div>

                        <div class="fw-bolder text-gray-900 fs-4">
                            LKR {{ number_format($inventoryValue, 2) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Low Stock --}}
            <div class="col-md-6 col-xl">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                                </div>
                            </div>

                            @if ($lowStockCount > 0)
                                <span class="badge badge-light-warning">
                                    Attention
                                </span>
                            @else
                                <span class="badge badge-light-success">
                                    Clear
                                </span>
                            @endif

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">
                            Low Stock
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($lowStockCount) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Out of Stock --}}
            <div class="col-md-6 col-xl">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-danger">
                                    <i class="bi bi-x-octagon-fill text-danger fs-3"></i>
                                </div>
                            </div>

                            @if ($outOfStockCount > 0)
                                <span class="badge badge-light-danger">
                                    Critical
                                </span>
                            @else
                                <span class="badge badge-light-success">
                                    Clear
                                </span>
                            @endif

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">
                            Out Of Stock
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($outOfStockCount) }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- OPERATIONS GRID --}}
        {{-- ========================================================= --}}
        <div class="row g-8">

            {{-- ===================================================== --}}
            {{-- LOW STOCK PRODUCTS --}}
            {{-- ===================================================== --}}
            <div class="col-xl-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Low Stock Products
                                </h3>

                                <div class="text-muted fs-8">
                                    Products that are close to or below their reorder level.
                                </div>

                            </div>

                        </div>


                        <div class="card-toolbar">

                            @if ($lowStockItems->isNotEmpty())
                                <span class="badge badge-light-warning px-3 py-2">
                                    {{ number_format($lowStockItems->count()) }}
                                    {{ Str::plural('Alert', $lowStockItems->count()) }}
                                </span>
                            @else
                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Healthy
                                </span>
                            @endif

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($lowStockItems as $stock)
                            @php
                                $productName = $stock->product?->name ?? 'Unknown Product';

                                $quantity = (float) $stock->quantity_on_hand;

                                $reorderLevel = (float) ($stock->product?->reorder_level ?? 0);

                                $isOutOfStock = $quantity <= 0;
                            @endphp


                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div
                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-45px me-4 flex-shrink-0">

                                            <div
                                                class="symbol-label {{ $isOutOfStock ? 'bg-light-danger' : 'bg-light-warning' }}">

                                                <i
                                                    class="bi {{ $isOutOfStock ? 'bi-box2-x text-danger' : 'bi-box-seam text-warning' }} fs-3"></i>

                                            </div>

                                        </div>


                                        <div>

                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">

                                                <div class="fw-bold text-gray-900">
                                                    {{ $productName }}
                                                </div>

                                                @if ($isOutOfStock)
                                                    <span class="badge badge-light-danger">
                                                        Out of Stock
                                                    </span>
                                                @else
                                                    <span class="badge badge-light-warning">
                                                        Low Stock
                                                    </span>
                                                @endif

                                            </div>


                                            <div class="text-muted fs-8">

                                                <i class="bi bi-shop me-1"></i>
                                                {{ $stock->branch?->name ?? 'Branch not specified' }}

                                                @if ($stock->product?->sku)
                                                    <span class="mx-1">·</span>
                                                    SKU {{ $stock->product->sku }}
                                                @endif

                                            </div>

                                        </div>

                                    </div>


                                    <div class="text-sm-end">

                                        <div
                                            class="fw-bolder {{ $isOutOfStock ? 'text-danger' : 'text-warning' }} fs-5">
                                            {{ number_format($quantity, 2) }}
                                        </div>

                                        <div class="text-muted fs-8">
                                            Quantity on hand
                                        </div>

                                        <div class="mt-2">

                                            <span class="badge badge-light">
                                                Reorder at {{ number_format($reorderLevel, 2) }}
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-15">

                                <div class="symbol symbol-80px mb-5">

                                    <div class="symbol-label bg-light-success rounded-circle">
                                        <i class="bi bi-check2-circle text-success fs-1"></i>
                                    </div>

                                </div>


                                <h4 class="fw-bold text-gray-900 mb-2">
                                    Stock Levels Look Healthy
                                </h4>

                                <div class="text-muted fs-7">
                                    There are currently no products below their reorder level.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RECENT MOVEMENTS --}}
            {{-- ===================================================== --}}
            <div class="col-xl-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-arrow-left-right text-info fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Recent Stock Movements
                                </h3>

                                <div class="text-muted fs-8">
                                    Latest inbound and outbound inventory activity.
                                </div>

                            </div>

                        </div>


                        @if ($recentMovements->isNotEmpty())
                            <div class="card-toolbar">

                                <span class="badge badge-light-info px-3 py-2">
                                    Recent Activity
                                </span>

                            </div>
                        @endif

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($recentMovements as $movement)
                            @php
                                $isIncoming = $movement->quantity >= 0;
                            @endphp


                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div
                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-45px me-4">

                                            <div
                                                class="symbol-label {{ $isIncoming ? 'bg-light-success' : 'bg-light-danger' }}">

                                                <i
                                                    class="bi {{ $isIncoming ? 'bi-arrow-down-left text-success' : 'bi-arrow-up-right text-danger' }} fs-3"></i>

                                            </div>

                                        </div>


                                        <div>

                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">

                                                <div class="fw-bold text-gray-900">
                                                    {{ $movement->product?->name ?? 'Unknown Product' }}
                                                </div>


                                                <span
                                                    class="badge {{ $isIncoming ? 'badge-light-success' : 'badge-light-danger' }}">
                                                    {{ $isIncoming ? 'Stock In' : 'Stock Out' }}
                                                </span>

                                            </div>


                                            <div class="text-muted fs-8">

                                                <i class="bi bi-building me-1"></i>
                                                {{ $movement->branch?->name ?? 'Branch not specified' }}

                                                <span class="mx-1">·</span>

                                                {{ $movement->type_label }}

                                            </div>

                                        </div>

                                    </div>


                                    <div class="text-sm-end">

                                        <div
                                            class="fw-bolder fs-5 {{ $isIncoming ? 'text-success' : 'text-danger' }}">
                                            {{ $isIncoming ? '+' : '' }}{{ $movement->quantity }}
                                        </div>


                                        <div class="text-muted fs-8 mt-1">

                                            <i class="bi bi-clock me-1"></i>

                                            {{ $movement->created_at?->format('M d, h:i A') }}

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-15">

                                <div class="symbol symbol-80px mb-5">

                                    <div class="symbol-label bg-light rounded-circle">
                                        <i class="bi bi-inboxes text-muted fs-1"></i>
                                    </div>

                                </div>


                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Stock Activity Yet
                                </h4>

                                <div class="text-muted fs-7 mb-6">
                                    Stock movements will appear here when inventory is received,
                                    adjusted or consumed.
                                </div>


                                <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light-primary">
                                    <i class="bi bi-sliders me-2"></i>
                                    View Stock Adjustments
                                </a>

                            </div>
                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>
