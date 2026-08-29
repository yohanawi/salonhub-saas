<x-default-layout>

    @section('title')
        Stock Adjustments
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.adjustments.index') }}
    @endsection


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
                                <i class="bi bi-sliders text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Stock Adjustments
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($adjustments->total()) }}
                                    {{ Str::plural('Adjustment', $adjustments->total()) }}
                                </span>

                                <span class="badge badge-light-warning px-3 py-2">
                                    Inventory Reconciliation
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Reconcile physical stock counts against system quantities
                                and maintain an accurate inventory audit trail.
                            </div>

                        </div>

                    </div>


                    @can('create', \App\Models\StockAdjustment::class)
                        <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            New Adjustment
                        </a>
                    @endcan

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- ADJUSTMENT HISTORY --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-clock-history text-primary fs-3"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Adjustment History
                        </h3>

                        <div class="text-muted fs-8">
                            Review completed inventory corrections and reconciliation records.
                        </div>

                    </div>

                </div>


                <div class="card-toolbar">

                    <span class="badge badge-light-primary px-3 py-2">
                        {{ number_format($adjustments->total()) }}
                        Total
                    </span>

                </div>

            </div>


            <div class="card-body pt-4">

                @if ($adjustments->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-200px">
                                        Adjustment
                                    </th>

                                    <th class="min-w-180px">
                                        Branch
                                    </th>

                                    <th class="min-w-150px">
                                        Reason
                                    </th>

                                    <th class="min-w-110px">
                                        Items
                                    </th>

                                    <th class="min-w-170px">
                                        Created By
                                    </th>

                                    <th class="min-w-170px">
                                        Date
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($adjustments as $adjustment)
                                    <tr>

                                        {{-- Adjustment --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-50px me-4 flex-shrink-0">
                                                    <div class="symbol-label bg-light-primary rounded-3">
                                                        <i class="bi bi-sliders text-primary fs-3"></i>
                                                    </div>
                                                </div>


                                                <div>

                                                    <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                                                        class="fw-bold text-gray-900 text-hover-primary fs-6">
                                                        {{ $adjustment->adjustment_number }}
                                                    </a>


                                                    <div class="text-muted fs-8 mt-1">
                                                        <i class="bi bi-journal-check me-1"></i>
                                                        Stock reconciliation
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Branch --}}
                                        <td>

                                            @if ($adjustment->branch)
                                                <div class="d-flex align-items-center">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-info">
                                                            <i class="bi bi-geo-alt-fill text-info"></i>
                                                        </div>
                                                    </div>


                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            {{ $adjustment->branch->name }}
                                                        </div>

                                                        <div class="text-muted fs-8">
                                                            Stock Location
                                                        </div>

                                                    </div>

                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Reason --}}
                                        <td>

                                            <span class="badge badge-light-info px-3 py-2">
                                                <i class="bi bi-chat-square-text me-1"></i>
                                                {{ $adjustment->reason_label }}
                                            </span>

                                        </td>


                                        {{-- Items --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light-success">
                                                        <i class="bi bi-box-seam text-success"></i>
                                                    </div>
                                                </div>


                                                <div>

                                                    <div class="fw-bolder text-gray-900">
                                                        {{ number_format($adjustment->items_count) }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        {{ Str::plural('Item', $adjustment->items_count) }}
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Created By --}}
                                        <td>

                                            @if ($adjustment->createdBy)
                                                <div class="d-flex align-items-center">

                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-primary">
                                                            <i class="bi bi-person-fill text-primary"></i>
                                                        </div>
                                                    </div>


                                                    <div>

                                                        <div class="fw-semibold text-gray-900">
                                                            {{ $adjustment->createdBy->name }}
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


                                        {{-- Date --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light">
                                                        <i class="bi bi-calendar3 text-muted"></i>
                                                    </div>
                                                </div>


                                                <div>

                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $adjustment->created_at?->format('M d, Y') }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        {{ $adjustment->created_at?->format('h:i A') }}
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                                                class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                                title="View Adjustment">
                                                <i class="bi bi-eye"></i>
                                            </a>

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
                                {{ $adjustments->firstItem() ?? 0 }}
                            </span>

                            to

                            <span class="fw-bold text-gray-800">
                                {{ $adjustments->lastItem() ?? 0 }}
                            </span>

                            of

                            <span class="fw-bold text-gray-800">
                                {{ number_format($adjustments->total()) }}
                            </span>

                            adjustments

                        </div>


                        @if ($adjustments->hasPages())
                            <div>
                                {{ $adjustments->withQueryString()->links() }}
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
                                <i class="bi bi-sliders text-primary fs-1"></i>
                            </div>

                        </div>


                        <h3 class="fw-bold text-gray-900 mb-3">
                            No Stock Adjustments Yet
                        </h3>


                        <div class="text-muted fs-7 mw-500px mx-auto mb-7">
                            Create a stock adjustment when physical inventory differs
                            from the system quantity and needs to be reconciled.
                        </div>


                        @can('create', \App\Models\StockAdjustment::class)
                            <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle-fill me-2"></i>
                                Create First Adjustment
                            </a>
                        @endcan

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-default-layout>
