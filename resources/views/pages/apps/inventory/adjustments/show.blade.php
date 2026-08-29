<x-default-layout>

    @section('title')
        Stock Adjustment
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.adjustments.show', $adjustment) }}
    @endsection


    @php
        $totalItems = $adjustment->items->count();

        $totalSystemQuantity = $adjustment->items->sum('system_quantity');

        $totalActualQuantity = $adjustment->items->sum('actual_quantity');

        $totalDifference = $adjustment->items->sum('difference');

        $differenceClass = match (true) {
            $totalDifference > 0 => 'success',
            $totalDifference < 0 => 'danger',
            default => 'secondary',
        };

        $differenceIcon = match (true) {
            $totalDifference > 0 => 'bi-arrow-down-left',
            $totalDifference < 0 => 'bi-arrow-up-right',
            default => 'bi-dash-lg',
        };
    @endphp


    <div id="kt_app_content_container">

        {{-- ========================================================= --}}
        {{-- ALERTS --}}
        {{-- ========================================================= --}}
        @include('pages/apps.inventory.partials._alerts')


        {{-- ========================================================= --}}
        {{-- HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-body p-8 p-lg-10">

                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-7">

                    <div class="d-flex align-items-start">

                        <div class="symbol symbol-70px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-sliders text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h1 class="fw-bolder text-gray-900 mb-0">
                                    {{ $adjustment->adjustment_number }}
                                </h1>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ $adjustment->reason_label }}
                                </span>

                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Posted
                                </span>

                            </div>


                            <div class="text-muted fs-6 mb-4">
                                Stock reconciliation record and product-level quantity changes.
                            </div>


                            <div class="d-flex flex-wrap align-items-center gap-4">

                                <div class="d-flex align-items-center">

                                    <div class="symbol symbol-35px me-2">
                                        <div class="symbol-label bg-light-info">
                                            <i class="bi bi-geo-alt-fill text-info"></i>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-muted fs-8">
                                            Branch
                                        </div>

                                        <div class="fw-semibold text-gray-900">
                                            {{ $adjustment->branch?->name ?? '—' }}
                                        </div>
                                    </div>

                                </div>


                                <div class="vr d-none d-md-block"></div>


                                <div class="d-flex align-items-center">

                                    <div class="symbol symbol-35px me-2">
                                        <div class="symbol-label bg-light">
                                            <i class="bi bi-calendar3 text-muted"></i>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-muted fs-8">
                                            Created
                                        </div>

                                        <div class="fw-semibold text-gray-900">
                                            {{ $adjustment->created_at?->format('M d, Y h:i A') }}
                                        </div>
                                    </div>

                                </div>


                                @if ($adjustment->createdBy)
                                    <div class="vr d-none d-md-block"></div>


                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-35px me-2">
                                            <div class="symbol-label bg-light-success">
                                                <i class="bi bi-person-fill text-success"></i>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-muted fs-8">
                                                Created By
                                            </div>

                                            <div class="fw-semibold text-gray-900">
                                                {{ $adjustment->createdBy->name }}
                                            </div>
                                        </div>

                                    </div>
                                @endif

                            </div>

                        </div>

                    </div>


                    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light align-self-start">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Adjustments
                    </a>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SUMMARY CARDS --}}
        {{-- ========================================================= --}}
        <div class="row g-6 mb-8">

            {{-- Products --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex justify-content-between align-items-start mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-box-seam-fill text-primary fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-primary">
                                Items
                            </span>

                        </div>


                        <div class="fw-bolder text-gray-900 fs-2 mb-1">
                            {{ number_format($totalItems) }}
                        </div>

                        <div class="text-muted fs-8">
                            {{ Str::plural('Product', $totalItems) }} adjusted
                        </div>

                    </div>

                </div>

            </div>


            {{-- System Qty --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex justify-content-between align-items-start mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-database-fill text-info fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-info">
                                System
                            </span>

                        </div>


                        <div class="fw-bolder text-gray-900 fs-2 mb-1">
                            {{ number_format($totalSystemQuantity) }}
                        </div>

                        <div class="text-muted fs-8">
                            Quantity before reconciliation
                        </div>

                    </div>

                </div>

            </div>


            {{-- Actual Qty --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex justify-content-between align-items-start mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-clipboard-check-fill text-success fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-success">
                                Physical
                            </span>

                        </div>


                        <div class="fw-bolder text-gray-900 fs-2 mb-1">
                            {{ number_format($totalActualQuantity) }}
                        </div>

                        <div class="text-muted fs-8">
                            Quantity physically counted
                        </div>

                    </div>

                </div>

            </div>


            {{-- Difference --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-6">

                        <div class="d-flex justify-content-between align-items-start mb-5">

                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-{{ $differenceClass }}">
                                    <i class="bi {{ $differenceIcon }} text-{{ $differenceClass }} fs-3"></i>
                                </div>
                            </div>

                            <span class="badge badge-light-{{ $differenceClass }}">
                                Difference
                            </span>

                        </div>


                        <div class="fw-bolder text-{{ $differenceClass }} fs-2 mb-1">

                            @if ($totalDifference > 0)
                                +
                            @endif

                            {{ number_format($totalDifference) }}

                        </div>

                        <div class="text-muted fs-8">
                            Net stock reconciliation
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="row g-8">

            {{-- ===================================================== --}}
            {{-- ADJUSTED PRODUCTS --}}
            {{-- ===================================================== --}}
            <div class="col-xl-9">

                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-boxes text-info fs-3"></i>
                                </div>
                            </div>


                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Adjusted Products
                                </h3>

                                <div class="text-muted fs-8">
                                    Compare system quantities with actual physical counts.
                                </div>

                            </div>

                        </div>


                        <div class="card-toolbar">

                            <span class="badge badge-light-info px-3 py-2">
                                {{ number_format($totalItems) }}
                                {{ Str::plural('Line', $totalItems) }}
                            </span>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @if ($adjustment->items->isNotEmpty())

                            <div class="table-responsive">

                                <table class="table align-middle table-row-dashed gy-6">

                                    <thead>

                                        <tr class="text-muted fw-bold fs-8 text-uppercase">

                                            <th class="min-w-260px">
                                                Product
                                            </th>

                                            <th class="text-end min-w-110px">
                                                System Qty
                                            </th>

                                            <th class="text-end min-w-110px">
                                                Actual Qty
                                            </th>

                                            <th class="text-end min-w-130px">
                                                Difference
                                            </th>

                                            <th class="min-w-220px">
                                                Reason
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody class="fw-semibold text-gray-700">

                                        @foreach ($adjustment->items as $item)
                                            @php
                                                $itemDifferenceClass = match (true) {
                                                    $item->difference > 0 => 'success',
                                                    $item->difference < 0 => 'danger',
                                                    default => 'secondary',
                                                };

                                                $itemDifferenceIcon = match (true) {
                                                    $item->difference > 0 => 'bi-arrow-down-left',
                                                    $item->difference < 0 => 'bi-arrow-up-right',
                                                    default => 'bi-dash-lg',
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


                                                        <div>

                                                            <div class="fw-bold text-gray-900 fs-6">
                                                                {{ $item->product?->name ?? 'Unknown Product' }}
                                                            </div>


                                                            @if ($item->product?->sku)
                                                                <div class="text-muted fs-8 mt-1">
                                                                    <i class="bi bi-upc-scan me-1"></i>
                                                                    SKU {{ $item->product->sku }}
                                                                </div>
                                                            @endif

                                                        </div>

                                                    </div>

                                                </td>


                                                {{-- System --}}
                                                <td class="text-end">

                                                    <div class="fw-bolder text-gray-900">
                                                        {{ number_format($item->system_quantity) }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        recorded
                                                    </div>

                                                </td>


                                                {{-- Actual --}}
                                                <td class="text-end">

                                                    <span class="badge badge-light-primary px-3 py-2">
                                                        {{ number_format($item->actual_quantity) }}
                                                    </span>

                                                </td>


                                                {{-- Difference --}}
                                                <td class="text-end">

                                                    <div class="fw-bolder text-{{ $itemDifferenceClass }}">

                                                        <i class="bi {{ $itemDifferenceIcon }} me-1"></i>

                                                        @if ($item->difference > 0)
                                                            +
                                                        @endif

                                                        {{ number_format($item->difference) }}

                                                    </div>


                                                    <div class="text-muted fs-8">

                                                        @if ($item->difference > 0)
                                                            Stock Added
                                                        @elseif ($item->difference < 0)
                                                            Stock Reduced
                                                        @else
                                                            No Change
                                                        @endif

                                                    </div>

                                                </td>


                                                {{-- Reason --}}
                                                <td>

                                                    <div class="d-flex align-items-start">

                                                        <div class="symbol symbol-35px me-3 flex-shrink-0">
                                                            <div class="symbol-label bg-light-warning">
                                                                <i class="bi bi-chat-square-text text-warning"></i>
                                                            </div>
                                                        </div>


                                                        <div>

                                                            <div class="fw-semibold text-gray-900">
                                                                {{ $item->reason ?: $adjustment->reason_label }}
                                                            </div>


                                                            @if ($item->reason)
                                                                <div class="text-muted fs-8">
                                                                    Line-specific reason
                                                                </div>
                                                            @else
                                                                <div class="text-muted fs-8">
                                                                    Adjustment reason
                                                                </div>
                                                            @endif

                                                        </div>

                                                    </div>

                                                </td>

                                            </tr>
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>
                        @else
                            <div class="text-center py-15">

                                <div class="symbol symbol-90px mb-6">
                                    <div class="symbol-label bg-light-primary rounded-circle">
                                        <i class="bi bi-box-seam text-primary fs-1"></i>
                                    </div>
                                </div>


                                <h3 class="fw-bold text-gray-900 mb-3">
                                    No Adjustment Items
                                </h3>

                                <div class="text-muted fs-7">
                                    No product lines are attached to this stock adjustment.
                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- SIDEBAR --}}
            {{-- ===================================================== --}}
            <div class="col-xl-3">

                {{-- Adjustment Information --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-info-circle text-primary"></i>
                                </div>
                            </div>


                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Adjustment Info
                                </h3>

                                <div class="text-muted fs-8">
                                    Reconciliation details.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        {{-- Number --}}
                        <div class="mb-5">

                            <div class="text-muted fs-8 mb-1">
                                Adjustment Number
                            </div>

                            <div class="fw-bold text-gray-900">
                                {{ $adjustment->adjustment_number }}
                            </div>

                        </div>


                        <div class="separator separator-dashed mb-5"></div>


                        {{-- Branch --}}
                        <div class="mb-5">

                            <div class="text-muted fs-8 mb-1">
                                Branch
                            </div>

                            <div class="fw-semibold text-gray-900">
                                <i class="bi bi-geo-alt-fill text-info me-2"></i>
                                {{ $adjustment->branch?->name ?? '—' }}
                            </div>

                        </div>


                        <div class="separator separator-dashed mb-5"></div>


                        {{-- Reason --}}
                        <div class="mb-5">

                            <div class="text-muted fs-8 mb-2">
                                Reason
                            </div>

                            <span class="badge badge-light-primary px-3 py-2">
                                {{ $adjustment->reason_label }}
                            </span>

                        </div>


                        @if ($adjustment->createdBy)
                            <div class="separator separator-dashed mb-5"></div>


                            <div>

                                <div class="text-muted fs-8 mb-2">
                                    Created By
                                </div>

                                <div class="d-flex align-items-center">

                                    <div class="symbol symbol-35px me-3">
                                        <div class="symbol-label bg-light-success">
                                            <i class="bi bi-person-fill text-success"></i>
                                        </div>
                                    </div>


                                    <div class="fw-semibold text-gray-900">
                                        {{ $adjustment->createdBy->name }}
                                    </div>

                                </div>

                            </div>
                        @endif

                    </div>

                </div>


                {{-- Reconciliation Result --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-{{ $differenceClass }}">
                                    <i class="bi {{ $differenceIcon }} text-{{ $differenceClass }}"></i>
                                </div>
                            </div>


                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Reconciliation
                                </h3>

                                <div class="text-muted fs-8">
                                    Overall stock result.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        <div class="text-center py-4">

                            <div class="symbol symbol-70px mb-4">

                                <div class="symbol-label bg-light-{{ $differenceClass }} rounded-circle">
                                    <i class="bi {{ $differenceIcon }} text-{{ $differenceClass }} fs-1"></i>
                                </div>

                            </div>


                            <div class="text-muted fs-8 mb-2">
                                Net Difference
                            </div>


                            <div class="fw-bolder text-{{ $differenceClass }} fs-2x mb-3">

                                @if ($totalDifference > 0)
                                    +
                                @endif

                                {{ number_format($totalDifference) }}

                            </div>


                            @if ($totalDifference > 0)
                                <span class="badge badge-light-success px-3 py-2">
                                    Stock Increased
                                </span>
                            @elseif ($totalDifference < 0)
                                <span class="badge badge-light-danger px-3 py-2">
                                    Stock Decreased
                                </span>
                            @else
                                <span class="badge badge-light-secondary px-3 py-2">
                                    Balanced
                                </span>
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>
