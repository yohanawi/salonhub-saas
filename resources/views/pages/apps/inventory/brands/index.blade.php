<x-default-layout>

    @section('title')
        Product Brands
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.brands.index') }}
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
                                <i class="bi bi-award-fill text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Product Brands
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($brands->total()) }}
                                    {{ Str::plural('Brand', $brands->total()) }}
                                </span>

                                <span class="badge badge-light-info px-3 py-2">
                                    Catalog Identity
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Manage the brands represented across your product catalog
                                and keep product identification consistent.
                            </div>

                        </div>

                    </div>


                    @can('create', \App\Models\ProductBrand::class)
                        <a href="{{ route('inventory.brands.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Add Brand
                        </a>
                    @endcan

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- BRAND DIRECTORY --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-award text-primary fs-3"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Brand Directory
                        </h3>

                        <div class="text-muted fs-8">
                            Review product brands, usage and availability.
                        </div>

                    </div>

                </div>


                <div class="card-toolbar">

                    <span class="badge badge-light-primary px-3 py-2">
                        {{ number_format($brands->total()) }}
                        Total
                    </span>

                </div>

            </div>


            <div class="card-body pt-4">

                @if ($brands->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed gy-6">

                            <thead>

                                <tr class="text-muted fw-bold fs-8 text-uppercase">

                                    <th class="min-w-280px">
                                        Brand
                                    </th>

                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-180px">
                                            Salon
                                        </th>
                                    @endif

                                    <th class="min-w-130px">
                                        Products
                                    </th>

                                    <th class="min-w-120px">
                                        Status
                                    </th>

                                    <th class="text-end min-w-100px">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($brands as $brand)
                                    <tr>

                                        {{-- Brand --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-50px me-4 flex-shrink-0">

                                                    <div class="symbol-label bg-light-primary rounded-3">
                                                        <i class="bi bi-award-fill text-primary fs-3"></i>
                                                    </div>

                                                </div>


                                                <div>

                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">

                                                        <div class="fw-bold text-gray-900 fs-6">
                                                            {{ $brand->name }}
                                                        </div>


                                                        @if ($brand->products_count > 0)
                                                            <span class="badge badge-light-info">
                                                                In Use
                                                            </span>
                                                        @else
                                                            <span class="badge badge-light">
                                                                No Products
                                                            </span>
                                                        @endif

                                                    </div>


                                                    <div class="text-muted fs-8 mw-400px">

                                                        {{ $brand->description ?: 'No description has been added for this brand.' }}

                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>

                                                @if ($brand->tenant)
                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="bi bi-shop text-info"></i>
                                                            </div>
                                                        </div>


                                                        <div>

                                                            <div class="fw-semibold text-gray-900">
                                                                {{ $brand->tenant->name }}
                                                            </div>

                                                            <div class="text-muted fs-8">
                                                                Brand Owner
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


                                        {{-- Product Count --}}
                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">

                                                    <div class="symbol-label bg-light-success">
                                                        <i class="bi bi-box-seam text-success"></i>
                                                    </div>

                                                </div>


                                                <div>

                                                    <div class="fw-bolder text-gray-900">
                                                        {{ number_format($brand->products_count) }}
                                                    </div>

                                                    <div class="text-muted fs-8">
                                                        {{ Str::plural('Product', $brand->products_count) }}
                                                    </div>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            <span
                                                class="badge badge-light-{{ $brand->is_active ? 'success' : 'danger' }} px-3 py-2">

                                                <i
                                                    class="bi {{ $brand->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }} me-1"></i>

                                                {{ $brand->is_active ? 'Active' : 'Inactive' }}

                                            </span>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="text-end">

                                            <a href="{{ route('inventory.brands.edit', $brand) }}"
                                                class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                                title="Edit Brand">
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
                                {{ $brands->firstItem() ?? 0 }}
                            </span>

                            to

                            <span class="fw-bold text-gray-800">
                                {{ $brands->lastItem() ?? 0 }}
                            </span>

                            of

                            <span class="fw-bold text-gray-800">
                                {{ number_format($brands->total()) }}
                            </span>

                            brands

                        </div>


                        @if ($brands->hasPages())
                            <div>
                                {{ $brands->withQueryString()->links() }}
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
                                <i class="bi bi-award-fill text-primary fs-1"></i>
                            </div>

                        </div>


                        <h3 class="fw-bold text-gray-900 mb-3">
                            No Product Brands Yet
                        </h3>


                        <div class="text-muted fs-7 mw-500px mx-auto mb-7">
                            Add brands to make products easier for staff and customers
                            to recognize across your inventory catalog.
                        </div>


                        @can('create', \App\Models\ProductBrand::class)
                            <a href="{{ route('inventory.brands.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle-fill me-2"></i>
                                Add First Brand
                            </a>
                        @endcan

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-default-layout>
