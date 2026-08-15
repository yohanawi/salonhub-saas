<x-default-layout>

    @section('title')
        Service Categories
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('service-categories.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">
                <span class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-success">
                        <i class="bi bi-check-lg fs-2 text-success"></i>
                    </span>
                </span>
                <div>
                    <div class="fw-bold text-gray-900">Success</div>
                    <div>{{ session('status') }}</div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-8">
                <span class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
                    </span>
                </span>
                <div>
                    <div class="fw-bold text-gray-900">Something went wrong</div>
                    <div>{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        <div class="d-flex flex-column flex-md-row justify-content-end align-items-md-center gap-5 mb-8">
            @can('create', \App\Models\ServiceCategory::class)
                <a href="{{ route('service-categories.create') }}" class="btn btn-primary px-6">
                    <i class="bi bi-plus-lg me-2"></i>
                    Add Category
                </a>
            @endcan
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('service-categories.index') }}"
                    class="align-items-end d-flex justify-content-between">

                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                        <input type="search" name="search" value="{{ request('search') }}"
                            class="form-control form-control-solid ps-11" placeholder="Search category name...">
                    </div>

                    <div class="d-flex align-items-center gap-5">
                        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                            <div class=" ">
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
                        @endif

                        <div class="">
                            <select name="status" class="form-select form-select-solid" data-control="select2"
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

                        <div class="">
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-light-primary flex-grow-1">
                                    <i class="bi bi-funnel me-2"></i>
                                    Filter
                                </button>
                                <a href="{{ route('service-categories.index') }}" class="btn btn-light">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Categories Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8 pb-4">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Category Directory
                        </h3>
                        <div class="text-muted fs-7">
                            {{ $categories->total() }}
                            {{ Str::plural('category', $categories->total()) }}
                            found
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th class="min-w-280px">
                                    Category
                                </th>
                                @if ($isSuperAdmin ?? false)
                                    <th class="min-w-180px">
                                        Salon
                                    </th>
                                @endif
                                <th class="min-w-120px">
                                    Services
                                </th>
                                <th class="min-w-100px">
                                    Sort Order
                                </th>
                                <th class="min-w-120px">
                                    Status
                                </th>
                                <th class="text-end min-w-120px">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($categories as $category)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="symbol symbol-50px me-4">
                                                <span class="symbol-label bg-light-info rounded-3">
                                                    <i class="bi bi-tag-fill fs-3 text-info"></i>
                                                </span>
                                            </span>
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

                                    @if ($isSuperAdmin ?? false)
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold text-gray-900">
                                                    {{ $category->tenant?->name ?? '-' }}
                                                </span>
                                                @if ($category->tenant?->slug)
                                                    <span class="text-muted fs-8">
                                                        {{ $category->tenant->slug }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif

                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="symbol symbol-35px me-3">
                                                <span class="symbol-label bg-light-primary">
                                                    <i class="bi bi-scissors text-primary"></i>
                                                </span>
                                            </span>
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
                                        @can('update', $category)
                                            <a href="{{ route('service-categories.edit', $category) }}"
                                                class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                                title="Edit Category">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ?? false ? 6 : 5 }}" class="text-center py-15">
                                        <div class="symbol symbol-100px mb-5">
                                            <span class="symbol-label bg-light-info rounded-circle">
                                                <i class="bi bi-tags fs-3x text-info"></i>
                                            </span>
                                        </div>
                                        <h3 class="fw-bold text-gray-900 mb-2">
                                            No service categories found
                                        </h3>
                                        <div class="text-muted mb-6">
                                            Create categories such as Hair, Nails,
                                            Beauty, Massage, Facial or Bridal.
                                        </div>
                                        @can('create', \App\Models\ServiceCategory::class)
                                            <a href="{{ route('service-categories.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-lg me-2"></i>
                                                Create First Category
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 pt-6 border-top">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-bold text-gray-900">
                                {{ $categories->firstItem() }}
                            </span>
                            -
                            <span class="fw-bold text-gray-900">
                                {{ $categories->lastItem() }}
                            </span>
                            of
                            <span class="fw-bold text-gray-900">
                                {{ $categories->total() }}
                            </span>
                            categories
                        </div>
                        <div>
                            {{ $categories->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .table tbody tr {
                transition:
                    background-color .2s ease,
                    transform .2s ease;
            }

            .table tbody tr:hover {
                background-color: rgba(var(--bs-primary-rgb), .025);
            }

            .symbol-label {
                transition: transform .2s ease;
            }

            .table tbody tr:hover .symbol-label {
                transform: scale(1.04);
            }
        </style>
    @endpush

</x-default-layout>
