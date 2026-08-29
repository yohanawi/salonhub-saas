<x-default-layout>

    @section('title')
        Product Units
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.units.index') }}
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
                                <i class="bi bi-rulers text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Product Units
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($units->total()) }}
                                    {{ Str::plural('Unit', $units->total()) }}
                                </span>

                                <span class="badge badge-light-info px-3 py-2">
                                    Measurement Catalog
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Manage the measurement units used when purchasing,
                                stocking and selling products.
                            </div>

                        </div>

                    </div>


                    @can('create', \App\Models\Unit::class)
                        <a href="{{ route('inventory.units.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Add Unit
                        </a>
                    @endcan

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- UNIT DIRECTORY --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-grid-3x3-gap-fill text-primary fs-3"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Unit Directory
                        </h3>

                        <div class="text-muted fs-8">
                            Units currently available across the product catalog.
                        </div>

                    </div>

                </div>


                <div class="card-toolbar">

                    <span class="badge badge-light-primary px-3 py-2">
                        {{ number_format($units->total()) }}
                        Total
                    </span>

                </div>

            </div>


            <div class="card-body pt-4">

                @if ($units->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-240px">
                                        Unit
                                    </th>

                                    @if ($isSuperAdmin)
                                        <th class="min-w-180px">
                                            Salon
                                        </th>
                                    @endif

                                    <th class="min-w-120px">
                                        Symbol
                                    </th>

                                    <th class="min-w-140px">
                                        Type
                                    </th>

                                    <th class="min-w-130px">
                                        Products
                                    </th>

                                    <th class="min-w-130px">
                                        Status
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($units as $unit)
                                    <tr>

                                        {{-- Unit --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-50px me-4 flex-shrink-0">
                                                    <div class="symbol-label bg-light-primary rounded-3">
                                                        <i class="bi bi-rulers text-primary fs-3"></i>
                                                    </div>
                                                </div>


                                                <div>

                                                    <div class="fw-bold text-gray-900 fs-6">
                                                        {{ $unit->name }}
                                                    </div>

                                                    <div class="text-muted fs-8 mt-1">
                                                        <i class="bi bi-bounding-box me-1"></i>
                                                        Measurement unit
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Salon --}}
                                        @if ($isSuperAdmin)
                                            <td>

                                                @if ($unit->tenant)
                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="bi bi-shop text-info"></i>
                                                            </div>
                                                        </div>


                                                        <div>

                                                            <div class="fw-semibold text-gray-900">
                                                                {{ $unit->tenant->name }}
                                                            </div>

                                                            <div class="text-muted fs-8">
                                                                Owner Salon
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


                                        {{-- Symbol --}}
                                        <td>

                                            <span class="badge badge-light-info px-3 py-2">
                                                <i class="bi bi-hash me-1"></i>
                                                {{ $unit->symbol }}
                                            </span>

                                        </td>


                                        {{-- Type --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light-warning">
                                                        <i class="bi bi-tags text-warning"></i>
                                                    </div>
                                                </div>


                                                <span class="fw-semibold text-gray-900">
                                                    {{ $unit->type_label }}
                                                </span>

                                            </div>

                                        </td>


                                        {{-- Products --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light-success">
                                                        <i class="bi bi-box-seam text-success"></i>
                                                    </div>
                                                </div>


                                                <div>

                                                    <div class="fw-bolder text-gray-900">
                                                        {{ number_format($unit->products_count) }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        {{ Str::plural('Product', $unit->products_count) }}
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            <span
                                                class="badge badge-light-{{ $unit->is_active ? 'success' : 'danger' }} px-3 py-2">

                                                <i
                                                    class="bi {{ $unit->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }} me-1"></i>

                                                {{ $unit->is_active ? 'Active' : 'Inactive' }}

                                            </span>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <a href="{{ route('inventory.units.edit', $unit) }}"
                                                class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                                title="Edit Unit">
                                                <i class="bi bi-pencil-square"></i>
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
                                {{ $units->firstItem() ?? 0 }}
                            </span>

                            to

                            <span class="fw-bold text-gray-800">
                                {{ $units->lastItem() ?? 0 }}
                            </span>

                            of

                            <span class="fw-bold text-gray-800">
                                {{ number_format($units->total()) }}
                            </span>

                            units

                        </div>


                        @if ($units->hasPages())
                            <div>
                                {{ $units->withQueryString()->links() }}
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
                                <i class="bi bi-rulers text-primary fs-1"></i>
                            </div>

                        </div>


                        <h3 class="fw-bold text-gray-900 mb-3">
                            No Product Units Yet
                        </h3>


                        <div class="text-muted fs-7 mw-500px mx-auto mb-7">
                            Create units of measure such as pieces, bottles,
                            milliliters, grams or kilograms for your inventory catalog.
                        </div>


                        @can('create', \App\Models\Unit::class)
                            <a href="{{ route('inventory.units.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle-fill me-2"></i>
                                Create First Unit
                            </a>
                        @endcan

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-default-layout>
