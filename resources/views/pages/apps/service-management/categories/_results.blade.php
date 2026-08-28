@php
    $hasActiveFilters = request()->filled('search') || request()->filled('tenant_id') || request()->filled('status');
@endphp

@if ($categories->isNotEmpty())
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                    <th class="min-w-280px">
                        Category
                    </th>

                    @if ($isSuperAdmin ?? false)
                        <th class="min-w-180px">
                            Salon
                        </th>
                    @endif

                    <th class="min-w-140px">
                        Services
                    </th>

                    <th class="min-w-120px">
                        Sort Order
                    </th>

                    <th class="min-w-120px">
                        Status
                    </th>

                    <th class="text-end min-w-100px">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="fw-semibold text-gray-700">
                @foreach ($categories as $category)
                    <tr>
                        {{-- Category --}}
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-50px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-light-info rounded-3">
                                        <i class="bi bi-tag-fill text-info fs-3"></i>
                                    </div>
                                </div>

                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-gray-900 fs-6 mb-1">
                                        {{ $category->name }}
                                    </span>

                                    <span class="text-muted fs-8">
                                        {{ Str::limit($category->description ?: $category->slug, 60) }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Salon --}}
                        @if ($isSuperAdmin ?? false)
                            <td>
                                @if ($category->tenant)
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-3">
                                            <div class="symbol-label bg-light-primary fw-bold text-primary">
                                                {{ strtoupper(substr($category->tenant->name, 0, 1)) }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="fw-semibold text-gray-900">
                                                {{ $category->tenant->name }}
                                            </div>

                                            @if ($category->tenant->slug)
                                                <div class="text-muted fs-8">
                                                    {{ $category->tenant->slug }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">
                                        —
                                    </span>
                                @endif
                            </td>
                        @endif

                        {{-- Services --}}
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-35px me-3">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-scissors text-primary"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="fw-bold text-gray-900">
                                        {{ number_format($category->services_count) }}
                                    </div>

                                    <div class="text-muted fs-9">
                                        {{ Str::plural('service', $category->services_count) }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="badge badge-light px-3 py-2">
                                <i class="bi bi-sort-numeric-down me-1"></i>
                                {{ $category->sort_order }}
                            </span>
                        </td>
                        <td>
                            @if ($category->is_active)
                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Active
                                </span>
                            @else
                                <span class="badge badge-light-danger px-3 py-2">
                                    <i class="bi bi-x-circle-fill me-1"></i>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('update', $category)
                                <a href="{{ route('service-categories.edit', $category) }}"
                                    class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                    title="Edit Category">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @else
                                <span class="text-muted">
                                    —
                                </span>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($categories->hasPages())
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 border-top border-gray-200 pt-6 mt-6">
            <div class="text-muted fs-7">
                Showing
                <span class="fw-bold text-gray-900">
                    {{ $categories->firstItem() }}
                </span>
                to
                <span class="fw-bold text-gray-900">
                    {{ $categories->lastItem() }}
                </span>
                of
                <span class="fw-bold text-gray-900">
                    {{ number_format($categories->total()) }}
                </span>
                categories
            </div>
            <div>
                {{ $categories->withQueryString()->links() }}
            </div>
        </div>
    @endif
@else
    <div class="text-center py-15">
        <div class="symbol symbol-90px mb-6">
            <div class="symbol-label {{ $hasActiveFilters ? 'bg-light-warning' : 'bg-light-info' }} rounded-circle">
                <i
                    class="bi {{ $hasActiveFilters ? 'bi-search' : 'bi-tags' }} fs-1 {{ $hasActiveFilters ? 'text-warning' : 'text-info' }}"></i>
            </div>
        </div>
        @if ($hasActiveFilters)
            <h3 class="fw-bold text-gray-900 mb-2">
                No categories match your search
            </h3>
            <div class="text-muted fs-6 mb-7">
                Try a different name, or adjust the salon and status filters.
            </div>
            <a href="{{ route('service-categories.index') }}" class="btn btn-light-info">
                <i class="bi bi-arrow-counterclockwise me-2"></i>
                Clear Search & Filters
            </a>
        @else
            <h3 class="fw-bold text-gray-900 mb-2">
                No service categories found
            </h3>
            <div class="text-muted fs-6 mb-7">
                Build a clear salon catalog with categories such as
                Hair, Nails, Beauty, Facial, Massage and Bridal.
            </div>
            @can('create', \App\Models\ServiceCategory::class)
                <a href="{{ route('service-categories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Create First Category
                </a>
            @endcan
        @endif
    </div>
@endif
