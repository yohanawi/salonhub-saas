<x-default-layout>

    @section('title')
        Services
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('services.index') }}
    @endsection

    <div id="kt_app_content_container">

        {{-- Alerts --}}
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-8">
                <span class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-success">
                        <i class="bi bi-check-lg fs-2 text-success"></i>
                    </span>
                </span>
                <div>
                    <div class="fw-bold">Success</div>
                    <div>{{ session('status') }}</div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-8">
                <span class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-lg fs-2 text-danger"></i>
                    </span>
                </span>
                <div>
                    <div class="fw-bold">Something went wrong</div>
                    <div>{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        {{-- Page Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-end align-items-md-center gap-5 mb-8">
            @can('create', \App\Models\Service::class)
                <a href="{{ route('services.create') }}" class="btn btn-primary px-6">
                    <i class="bi bi-plus-lg me-2"></i>
                    Add New Service
                </a>
            @endcan
        </div>

        {{-- Filters --}}
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('services.index') }}" class="row g-4 align-items-end">
                    @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                        <div class="col-xl-3 col-md-6">
                            <label class="form-label fw-semibold text-gray-700">
                                Salon
                            </label>
                            <select name="tenant_id" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All salons</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Search
                        </label>
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                            <input type="search" name="search" value="{{ request('search') }}"
                                class="form-control form-control-solid ps-11" placeholder="Service name...">
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Category
                        </label>
                        <select name="category_id" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Branch
                        </label>
                        <select name="branch_id" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold text-gray-700">
                            Status
                        </label>
                        <select name="status" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')}>
                                Active
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')}>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="{{ route('services.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Reset
                        </a>
                        <button type="submit" class="btn btn-light-primary">
                            <i class="bi bi-funnel me-2"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Services --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Available Services
                        </h3>
                        <div class="text-muted fs-7">
                            {{ $services->total() }}
                            {{ Str::plural('service', $services->total()) }}
                            found
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">
                                    Service
                                </th>
                                @if ($isSuperAdmin ?? false)
                                    <th class="min-w-150px">
                                        Salon
                                    </th>
                                @endif
                                <th class="min-w-140px">
                                    Category
                                </th>
                                <th class="min-w-140px">
                                    Default Price
                                </th>
                                <th class="min-w-120px">
                                    Duration
                                </th>
                                <th class="min-w-100px">
                                    Branches
                                </th>
                                <th class="min-w-110px">
                                    Status
                                </th>
                                <th class="text-end min-w-180px">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($services as $service)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-4">
                                                <span class="symbol-label bg-light-primary rounded-3">
                                                    <i class="bi bi-scissors fs-2 text-primary"></i>
                                                </span>
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

                                    @if ($isSuperAdmin ?? false)
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold text-gray-800">
                                                    {{ $service->tenant?->name ?? '-' }}
                                                </span>
                                                @if ($service->tenant?->slug)
                                                    <span class="text-muted fs-8">
                                                        {{ $service->tenant->slug }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                    <td>
                                        @if ($service->category)
                                            <span class="badge badge-light-info px-3 py-2">
                                                <i class="bi bi-tag me-1"></i>
                                                {{ $service->category->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                Uncategorized
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-gray-900 fs-6">
                                                LKR
                                                {{ number_format((float) ($service->default_price ?? $service->price), 2) }}
                                            </span>
                                            <span class="text-muted fs-8">
                                                Default price
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $duration =
                                                $service->default_duration_minutes ?? $service->duration_minutes;
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <span class="symbol symbol-30px me-2">
                                                <span class="symbol-label bg-light">
                                                    <i class="bi bi-clock text-muted"></i>
                                                </span>
                                            </span>
                                            <div>
                                                <div class="fw-bold text-gray-900">
                                                    {{ $duration }} min
                                                </div>
                                                @if ($duration >= 60)
                                                    <div class="text-muted fs-8">
                                                        {{ floor($duration / 60) }}
                                                        hr
                                                        @if ($duration % 60)
                                                            {{ $duration % 60 }}
                                                            min
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('services.show', $service) }}"
                                            class="badge badge-light-primary px-3 py-2 text-hover-primary">
                                            <i class="bi bi-shop me-1"></i>
                                            {{ number_format($service->branches_count) }}
                                            {{ Str::plural('Branch', $service->branches_count) }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($service->is_active)
                                            <span class="badge badge-light-success px-3 py-2">
                                                <span class="bullet bullet-dot bg-success me-2"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="badge badge-light-danger px-3 py-2">
                                                <span class="bullet bullet-dot bg-danger me-2"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('services.show', $service) }}"
                                                class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                                title="Manage Service">
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
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ?? false ? 8 : 7 }}" class="text-center py-15">
                                        <div class="symbol symbol-100px mb-5">
                                            <span class="symbol-label bg-light-primary rounded-circle">
                                                <i class="bi bi-scissors fs-3x text-primary"></i>
                                            </span>
                                        </div>
                                        <h3 class="fw-bold text-gray-900 mb-2">
                                            No services found
                                        </h3>
                                        <div class="text-muted mb-6">
                                            Start building your salon catalog by creating your first service.
                                        </div>
                                        @can('create', \App\Models\Service::class)
                                            <a href="{{ route('services.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-lg me-2"></i>
                                                Create First Service
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($services->hasPages())
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 pt-6 border-top">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-bold text-gray-800">
                                {{ $services->firstItem() }}
                            </span>
                            -
                            <span class="fw-bold text-gray-800">
                                {{ $services->lastItem() }}
                            </span>
                            of
                            <span class="fw-bold text-gray-800">
                                {{ $services->total() }}
                            </span>
                            services
                        </div>
                        <div>
                            {{ $services->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
