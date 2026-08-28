@php
    $hasActiveFilters =
        request()->filled('search') ||
        request()->filled('tenant_id') ||
        request()->filled('category_id') ||
        request()->filled('branch_id') ||
        request()->filled('status');
@endphp

@if ($services->isNotEmpty())
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                    <th class="min-w-260px">
                        Service
                    </th>
                    @if ($isSuperAdmin ?? false)
                        <th class="min-w-160px">
                            Salon
                        </th>
                    @endif
                    <th class="min-w-150px">
                        Category
                    </th>
                    <th class="min-w-150px">
                        Default Price
                    </th>
                    <th class="min-w-130px">
                        Duration
                    </th>
                    <th class="min-w-120px">
                        Branches
                    </th>
                    <th class="min-w-110px">
                        Status
                    </th>
                    <th class="text-end min-w-120px">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody id="services-table-body" class="fw-semibold text-gray-700">
                @foreach ($services as $service)
                    @php
                        $duration = $service->default_duration_minutes ?? $service->duration_minutes;
                    @endphp

                    <tr>
                        {{-- Service --}}
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-50px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-light-primary rounded-3">
                                        <i class="bi bi-scissors fs-2 text-primary"></i>
                                    </div>
                                </div>

                                <div class="d-flex flex-column">
                                    <a href="{{ route('services.show', $service) }}"
                                        class="text-gray-900 text-hover-primary fw-bold fs-6 mb-1">
                                        {{ $service->name }}
                                    </a>
                                    <span class="text-muted fs-8">
                                        {{ Str::limit($service->description ?: $service->slug, 55) }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Salon --}}
                        @if ($isSuperAdmin ?? false)
                            <td>
                                @if ($service->tenant)
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-3">
                                            <div class="symbol-label bg-light-info fw-bold text-info">
                                                {{ strtoupper(substr($service->tenant->name, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-gray-900">
                                                {{ $service->tenant->name }}
                                            </div>
                                            @if ($service->tenant->slug)
                                                <div class="text-muted fs-8">
                                                    {{ $service->tenant->slug }}
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

                        {{-- Category --}}
                        <td>
                            @if ($service->category)
                                <span class="badge badge-light-info px-3 py-2">
                                    <i class="bi bi-tag me-1"></i>
                                    {{ $service->category->name }}
                                </span>
                            @else
                                <span class="badge badge-light">
                                    Uncategorized
                                </span>
                            @endif
                        </td>

                        {{-- Price --}}
                        <td>
                            <div>
                                <div class="fw-bolder text-gray-900">
                                    LKR
                                    {{ number_format((float) ($service->default_price ?? $service->price), 2) }}
                                </div>
                                <div class="text-muted fs-8 mt-1">
                                    Default price
                                </div>
                            </div>
                        </td>

                        {{-- Duration --}}
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-35px me-3">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-clock text-warning"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold text-gray-900">
                                        {{ $duration }} min
                                    </div>
                                    @if ($duration >= 60)
                                        <div class="text-muted fs-8">

                                            {{ floor($duration / 60) }} hr

                                            @if ($duration % 60)
                                                {{ $duration % 60 }} min
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Branches --}}
                        <td>
                            <a href="{{ route('services.show', $service) }}"
                                class="badge badge-light-primary px-3 py-2">
                                <i class="bi bi-shop me-1"></i>
                                {{ number_format($service->branches_count) }}
                                {{ Str::plural('Branch', $service->branches_count) }}
                            </a>
                        </td>

                        {{-- Status --}}
                        <td>
                            @if ($service->is_active)
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

                        {{-- Actions --}}
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a href="{{ route('services.show', $service) }}"
                                    class="btn btn-sm btn-icon btn-light-primary"
                                    data-bs-toggle="tooltip" title="Manage Service">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $service)
                                    <a href="{{ route('services.edit', $service) }}"
                                        class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip"
                                        title="Edit Service">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($services->hasPages())
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 border-top border-gray-200 pt-6 mt-6">
            <div class="text-muted fs-7">
                Showing
                <span class="fw-bold text-gray-800">
                    {{ $services->firstItem() }}
                </span>
                to
                <span class="fw-bold text-gray-800">
                    {{ $services->lastItem() }}
                </span>
                of
                <span class="fw-bold text-gray-800">
                    {{ number_format($services->total()) }}
                </span>
                services
            </div>
            <div>
                {{ $services->withQueryString()->links() }}
            </div>
        </div>
    @endif
@else
    {{-- ================================================= --}}
    {{-- EMPTY STATE --}}
    {{-- ================================================= --}}
    <div class="text-center py-15">
        <div class="symbol symbol-90px mb-6">
            <div class="symbol-label {{ $hasActiveFilters ? 'bg-light-warning' : 'bg-light-primary' }} rounded-circle">
                <i
                    class="bi {{ $hasActiveFilters ? 'bi-search' : 'bi-scissors' }} fs-1 {{ $hasActiveFilters ? 'text-warning' : 'text-primary' }}"></i>
            </div>
        </div>
        @if ($hasActiveFilters)
            <h2 class="fw-bolder text-gray-900 mb-3">
                No services match your search
            </h2>
            <div class="text-muted fs-6 mb-7">
                Try a different name, or adjust the salon, category, branch or status filters.
            </div>
            <a href="{{ route('services.index') }}" class="btn btn-light-primary">
                <i class="bi bi-arrow-counterclockwise me-2"></i>
                Clear Search & Filters
            </a>
        @else
            <h2 class="fw-bolder text-gray-900 mb-3">
                No services found
            </h2>
            <div class="text-muted fs-6 mb-7">
                Start building your salon catalog by creating
                services such as haircuts, styling, coloring,
                treatments and beauty services.
            </div>
            @can('create', \App\Models\Service::class)
                <a href="{{ route('services.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Create First Service
                </a>
            @endcan
        @endif
    </div>
@endif
