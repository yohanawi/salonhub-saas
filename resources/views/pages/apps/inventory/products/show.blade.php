<x-default-layout>

    @section('title')
        Product
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.show', $product) }}
    @endsection


    @php
        $totalOnHand = $product->inventories->sum('quantity_on_hand');
        $totalReserved = $product->inventories->sum('quantity_reserved');
        $totalAvailable = $product->inventories->sum('available_quantity');

        $inventoryValue = $product->inventories->sum(
            fn($stock) => (float) $stock->quantity_on_hand * (float) $stock->average_cost,
        );

        $branchCount = $product->inventories->count();

        $stockHealth = match (true) {
            $totalOnHand <= 0 => [
                'class' => 'danger',
                'label' => 'Out of Stock',
                'icon' => 'bi-x-octagon-fill',
            ],
            $totalOnHand <= ($product->reorder_level ?? 0) => [
                'class' => 'warning',
                'label' => 'Low Stock',
                'icon' => 'bi-exclamation-triangle-fill',
            ],
            default => [
                'class' => 'success',
                'label' => 'Healthy Stock',
                'icon' => 'bi-check-circle-fill',
            ],
        };
    @endphp


    <div id="kt_app_content_container">

        {{-- ========================================================= --}}
        {{-- ALERTS --}}
        {{-- ========================================================= --}}
        @include('pages/apps.inventory.partials._alerts')


        {{-- ========================================================= --}}
        {{-- PRODUCT HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-body p-8 p-lg-10">

                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-8">

                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-6">

                        <div class="symbol symbol-80px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-box-seam-fill text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">

                                <h1 class="fw-bolder text-gray-900 mb-0">
                                    {{ $product->name }}
                                </h1>


                                <span class="badge badge-light-info px-3 py-2">
                                    {{ $product->product_type_label }}
                                </span>


                                <span
                                    class="badge badge-light-{{ $product->is_active ? 'success' : 'danger' }} px-3 py-2">
                                    <i
                                        class="bi {{ $product->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }} me-1"></i>

                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>


                                <span class="badge badge-light-{{ $stockHealth['class'] }} px-3 py-2">
                                    <i class="bi {{ $stockHealth['icon'] }} me-1"></i>
                                    {{ $stockHealth['label'] }}
                                </span>

                            </div>


                            <div class="d-flex flex-wrap gap-4 text-muted fs-7 mb-3">

                                <span>
                                    <i class="bi bi-upc-scan me-1"></i>
                                    SKU {{ $product->sku }}
                                </span>


                                @if ($product->barcode)
                                    <span>
                                        <i class="bi bi-upc me-1"></i>
                                        {{ $product->barcode }}
                                    </span>
                                @endif


                                @if ($product->category)
                                    <span>
                                        <i class="bi bi-tags me-1"></i>
                                        {{ $product->category->name }}
                                    </span>
                                @endif


                                @if ($product->brand)
                                    <span>
                                        <i class="bi bi-award me-1"></i>
                                        {{ $product->brand->name }}
                                    </span>
                                @endif

                            </div>


                            <div class="text-gray-700 fs-7">
                                {{ $product->description ?: 'No product description has been added yet.' }}
                            </div>

                        </div>

                    </div>


                    <div class="d-flex flex-wrap gap-3">

                        <a href="{{ route('inventory.products.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>


                        @can('update', $product)
                            <a href="{{ route('inventory.products.edit', $product) }}" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Product
                            </a>
                        @endcan

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- INVENTORY SUMMARY --}}
        {{-- ========================================================= --}}
        <div class="row g-5 mb-8">

            {{-- On Hand --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-boxes text-primary fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-primary">
                                Stock
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Quantity On Hand
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($totalOnHand) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Available --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-check2-square text-success fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-success">
                                Available
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Available Stock
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($totalAvailable) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Reserved --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center justify-content-between mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-lock text-warning fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-warning">
                                Reserved
                            </span>

                        </div>


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Reserved Stock
                        </div>

                        <div class="fw-bolder text-gray-900 fs-2x">
                            {{ number_format($totalReserved) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Inventory Value --}}
            <div class="col-md-6 col-xl-3">

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


                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            Inventory Value
                        </div>

                        <div class="fw-bolder text-gray-900 fs-5">
                            LKR {{ number_format($inventoryValue, 2) }}
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
            <div class="col-xl-4">


                {{-- ================================================= --}}
                {{-- PRODUCT OVERVIEW --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-info-circle text-primary fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Product Overview
                                </h3>

                                <div class="text-muted fs-8">
                                    Pricing, classification and inventory settings.
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        {{-- Category --}}
                        <div class="d-flex align-items-center justify-content-between gap-4 py-4">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-tags text-primary"></i>
                                    </div>
                                </div>

                                <div class="text-muted">
                                    Category
                                </div>

                            </div>

                            <div class="fw-semibold text-gray-900 text-end">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </div>

                        </div>


                        <div class="separator separator-dashed"></div>


                        {{-- Brand --}}
                        <div class="d-flex align-items-center justify-content-between gap-4 py-4">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-award text-info"></i>
                                    </div>
                                </div>

                                <div class="text-muted">
                                    Brand
                                </div>

                            </div>

                            <div class="fw-semibold text-gray-900 text-end">
                                {{ $product->brand?->name ?? 'No brand' }}
                            </div>

                        </div>


                        <div class="separator separator-dashed"></div>


                        {{-- Unit --}}
                        <div class="d-flex align-items-center justify-content-between gap-4 py-4">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-rulers text-warning"></i>
                                    </div>
                                </div>

                                <div class="text-muted">
                                    Stock Unit
                                </div>

                            </div>

                            <div class="fw-semibold text-gray-900 text-end">

                                @if ($product->unit)
                                    {{ $product->unit->name }}
                                    ({{ $product->unit->symbol }})
                                @else
                                    No unit
                                @endif

                            </div>

                        </div>


                        <div class="separator separator-dashed"></div>


                        {{-- Reorder --}}
                        <div class="d-flex align-items-center justify-content-between gap-4 py-4">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-danger">
                                        <i class="bi bi-bell text-danger"></i>
                                    </div>
                                </div>

                                <div class="text-muted">
                                    Reorder Level
                                </div>

                            </div>

                            <div class="fw-bold text-gray-900">
                                {{ number_format($product->reorder_level) }}
                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- PRICING --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-cash-coin text-success fs-3"></i>
                                </div>
                            </div>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Pricing
                                </h3>

                                <div class="text-muted fs-8">
                                    Cost, retail and current gross margin.
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        <div class="row g-4">

                            <div class="col-6">

                                <div class="rounded-4 bg-light p-5 h-100">

                                    <div class="text-muted fs-8 mb-2">
                                        Cost Price
                                    </div>

                                    <div class="fw-bold text-gray-900 fs-6">
                                        LKR {{ number_format((float) $product->cost_price, 2) }}
                                    </div>

                                </div>

                            </div>


                            <div class="col-6">

                                <div class="rounded-4 bg-light-primary p-5 h-100">

                                    <div class="text-muted fs-8 mb-2">
                                        Selling Price
                                    </div>

                                    <div class="fw-bold text-primary fs-6">
                                        LKR {{ number_format((float) $product->selling_price, 2) }}
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="rounded-4 bg-light-success p-5 mt-5">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <div class="text-muted fs-8 mb-1">
                                        Gross Margin
                                    </div>

                                    <div class="fw-bolder text-success fs-4">
                                        LKR {{ number_format($product->margin_amount, 2) }}
                                    </div>

                                </div>


                                <span class="badge badge-light-success fs-6 px-4 py-3">
                                    {{ number_format($product->margin_percentage, 2) }}%
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- STOCK HEALTH --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center mb-6">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-{{ $stockHealth['class'] }}">
                                    <i
                                        class="bi {{ $stockHealth['icon'] }} text-{{ $stockHealth['class'] }} fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <div class="fw-bold text-gray-900">
                                    Stock Health
                                </div>

                                <div class="text-muted fs-8">
                                    Current inventory condition.
                                </div>

                            </div>

                        </div>


                        <div class="rounded-4 bg-light-{{ $stockHealth['class'] }} p-5">

                            <div class="fw-bolder text-{{ $stockHealth['class'] }} fs-5 mb-1">
                                {{ $stockHealth['label'] }}
                            </div>

                            <div class="text-gray-700 fs-8">
                                {{ number_format($totalOnHand) }} units on hand
                                across {{ number_format($branchCount) }}
                                {{ Str::plural('branch', $branchCount) }}.
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT COLUMN --}}
            {{-- ===================================================== --}}
            <div class="col-xl-8">


                {{-- ================================================= --}}
                {{-- STOCK BY BRANCH --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-buildings text-info fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Stock by Branch
                                </h3>

                                <div class="text-muted fs-8">
                                    Branch-level stock quantity and availability.
                                </div>

                            </div>

                        </div>


                        <div class="card-toolbar">

                            <span class="badge badge-light-info px-3 py-2">
                                {{ number_format($branchCount) }}
                                {{ Str::plural('Location', $branchCount) }}
                            </span>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @if ($product->inventories->isNotEmpty())

                            <div class="table-responsive">

                                <table class="table align-middle table-row-dashed gy-5">

                                    <thead>

                                        <tr class="text-muted fw-bold fs-8 text-uppercase">

                                            <th class="min-w-200px">
                                                Branch
                                            </th>

                                            <th class="text-end min-w-100px">
                                                On Hand
                                            </th>

                                            <th class="text-end min-w-100px">
                                                Reserved
                                            </th>

                                            <th class="text-end min-w-110px">
                                                Available
                                            </th>

                                            <th class="text-end min-w-140px">
                                                Avg. Cost
                                            </th>

                                            <th class="min-w-120px">
                                                Status
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        @foreach ($product->inventories as $stock)
                                            @php
                                                $statusClass = match ($stock->stock_status) {
                                                    'in_stock' => 'success',
                                                    'low_stock' => 'warning',
                                                    default => 'danger',
                                                };
                                            @endphp


                                            <tr>

                                                {{-- Branch --}}
                                                <td>

                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-40px me-4">
                                                            <div class="symbol-label bg-light-primary">
                                                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                                            </div>
                                                        </div>

                                                        <div>

                                                            <div class="fw-bold text-gray-900">
                                                                {{ $stock->branch?->name ?? 'Unknown Branch' }}
                                                            </div>

                                                            <div class="text-muted fs-8">
                                                                Inventory location
                                                            </div>

                                                        </div>

                                                    </div>

                                                </td>


                                                {{-- On Hand --}}
                                                <td class="text-end">

                                                    <div class="fw-bold text-gray-900">
                                                        {{ number_format($stock->quantity_on_hand) }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        units
                                                    </div>

                                                </td>


                                                {{-- Reserved --}}
                                                <td class="text-end">

                                                    <div class="fw-semibold text-warning">
                                                        {{ number_format($stock->quantity_reserved) }}
                                                    </div>

                                                </td>


                                                {{-- Available --}}
                                                <td class="text-end">

                                                    <div class="fw-bold text-success">
                                                        {{ number_format($stock->available_quantity) }}
                                                    </div>

                                                </td>


                                                {{-- Average Cost --}}
                                                <td class="text-end">

                                                    <div class="fw-semibold text-gray-900">
                                                        LKR {{ number_format((float) $stock->average_cost, 2) }}
                                                    </div>

                                                </td>


                                                {{-- Status --}}
                                                <td>

                                                    <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                                        {{ $stock->stock_status_label }}
                                                    </span>

                                                </td>

                                            </tr>
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>
                        @else
                            <div class="text-center py-15">

                                <div class="symbol symbol-80px mb-5">
                                    <div class="symbol-label bg-light rounded-circle">
                                        <i class="bi bi-inbox text-muted fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Branch Stock Recorded
                                </h4>

                                <div class="text-muted fs-7">
                                    Stock will appear here once inventory is assigned
                                    to a branch.
                                </div>

                            </div>

                        @endif

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- RECENT MOVEMENTS --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-clock-history text-warning fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Recent Stock Movements
                                </h3>

                                <div class="text-muted fs-8">
                                    Latest stock additions, removals and adjustments.
                                </div>

                            </div>

                        </div>


                        @if ($product->stockMovements->isNotEmpty())
                            <div class="card-toolbar">

                                <span class="badge badge-light-warning px-3 py-2">
                                    Last 10 Activities
                                </span>

                            </div>
                        @endif

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($product->stockMovements
                                ->sortByDesc('created_at')
                                ->take(10)
                            as $movement)
                            @php
                                $incoming = $movement->quantity >= 0;
                            @endphp


                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                                <div
                                    class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-45px me-4">

                                            <div
                                                class="symbol-label {{ $incoming ? 'bg-light-success' : 'bg-light-danger' }}">

                                                <i
                                                    class="bi {{ $incoming ? 'bi-arrow-down-left text-success' : 'bi-arrow-up-right text-danger' }} fs-3"></i>

                                            </div>

                                        </div>


                                        <div>

                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">

                                                <div class="fw-bold text-gray-900">
                                                    {{ $movement->type_label }}
                                                </div>


                                                <span
                                                    class="badge {{ $incoming ? 'badge-light-success' : 'badge-light-danger' }}">
                                                    {{ $incoming ? 'Stock In' : 'Stock Out' }}
                                                </span>

                                            </div>


                                            <div class="text-muted fs-8">

                                                <i class="bi bi-building me-1"></i>
                                                {{ $movement->branch?->name ?? 'Branch not specified' }}

                                                <span class="mx-1">·</span>

                                                <i class="bi bi-clock me-1"></i>
                                                {{ $movement->created_at?->format('M d, Y h:i A') }}

                                            </div>

                                        </div>

                                    </div>


                                    <div class="d-flex align-items-center gap-6">

                                        <div class="text-end">

                                            <div
                                                class="fw-bolder fs-5 {{ $incoming ? 'text-success' : 'text-danger' }}">
                                                {{ $incoming ? '+' : '' }}{{ $movement->quantity }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Change
                                            </div>

                                        </div>


                                        <div class="border-start h-45px"></div>


                                        <div class="text-end">

                                            <div class="fw-semibold text-gray-900">
                                                {{ $movement->quantity_before }}
                                                →
                                                {{ $movement->quantity_after }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Balance
                                            </div>

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
                                    No Stock Movements Yet
                                </h4>

                                <div class="text-muted fs-7">
                                    Inventory movement history will appear here
                                    after stock is received, consumed or adjusted.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>
