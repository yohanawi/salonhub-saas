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
                <div class="symbol symbol-45px me-4">
                    <div class="symbol-label bg-light-success">
                        <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Success
                    </div>
                    <div>
                        {{ session('status') }}
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">
                <div class="symbol symbol-45px me-4 flex-shrink-0">
                    <div class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Something went wrong
                    </div>
                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-7">
                    <div class="d-flex align-items-center gap-5">
                        <div class="symbol symbol-60px flex-shrink-0">
                            <div class="symbol-label bg-light-info rounded-4">
                                <i class="bi bi-tags-fill text-info fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Service Categories
                                </h3>
                                <span class="badge badge-light-info px-3 py-2">
                                    Catalog Structure
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                Organize salon services into clear categories for staff, POS and bookings.
                            </div>
                        </div>
                    </div>
                    @can('create', \App\Models\ServiceCategory::class)
                        <a href="{{ route('service-categories.create') }}" class="btn btn-primary align-self-start btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Add Category
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        @php
            $hasActiveFilters =
                request()->filled('search') || request()->filled('tenant_id') || request()->filled('status');
        @endphp

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8 flex-wrap gap-3">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-grid-3x3-gap text-info fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Category Directory
                        </h3>
                        <div class="text-muted fs-8">
                            {{ number_format($categories->total()) }}
                            {{ Str::plural('category', $categories->total()) }}
                            found
                        </div>
                    </div>
                </div>

                <div class="card-toolbar">
                    <form method="GET" action="{{ route('service-categories.index') }}"
                        class="d-flex flex-wrap align-items-center gap-3">
                        <div class="position-relative">
                            <i
                                class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted fs-5"></i>
                            <input type="search" name="search" id="category-search-input"
                                value="{{ request('search') }}" autocomplete="off"
                                class="form-control form-control-solid ps-12 w-200px w-md-250px"
                                placeholder="Search category name...">
                            <span id="category-search-spinner"
                                class="spinner-border spinner-border-sm text-info position-absolute top-50 translate-middle-y end-0 me-4 d-none"
                                role="status" aria-hidden="true"></span>
                        </div>

                        {{-- Filter Button --}}
                        <div>
                            <button type="button"
                                class="btn btn-light-info d-flex align-items-center position-relative"
                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="bi bi-funnel-fill me-2"></i>
                                Filter
                                @if ($hasActiveFilters)
                                    <span
                                        class="badge badge-circle badge-danger position-absolute top-0 start-100 translate-middle p-0"
                                        style="width: 10px; height: 10px;"></span>
                                @endif
                            </button>

                            {{-- Filter Dropdown --}}
                            <div class="menu menu-sub menu-sub-dropdown menu-column w-300px p-6" data-kt-menu="true">
                                <div class="fs-5 text-gray-900 fw-bold mb-5">
                                    Filter Categories
                                </div>

                                <div class="row g-4">
                                    {{-- Salon --}}
                                    @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-gray-700 fs-8">
                                                Salon
                                            </label>
                                            <select name="tenant_id" class="form-select form-select-sm"
                                                data-control="select2" data-hide-search="true">
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

                                    {{-- Status --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-gray-700 fs-8">
                                            Status
                                        </label>
                                        <select name="status" class="form-select form-select-sm" data-control="select2"
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
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-6">
                                    <a href="{{ route('service-categories.index') }}" class="btn btn-sm btn-light">
                                        Reset
                                    </a>
                                    <button type="submit" class="btn btn-sm btn-info">
                                        <i class="bi bi-funnel-fill me-2"></i>
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if ($hasActiveFilters)
                <div class="px-9 pb-4">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted fs-8 fw-semibold me-1">
                            Active:
                        </span>
                        @if (request()->filled('search'))
                            <span class="badge badge-light-primary px-3 py-2">
                                <i class="bi bi-search me-1"></i>
                                {{ request('search') }}
                            </span>
                        @endif

                        @if (request()->filled('tenant_id'))
                            @php
                                $selectedTenant = $tenants->firstWhere('id', (int) request('tenant_id'));
                            @endphp
                            @if ($selectedTenant)
                                <span class="badge badge-light-dark px-3 py-2">
                                    <i class="bi bi-shop-window me-1"></i>
                                    {{ $selectedTenant->name }}
                                </span>
                            @endif
                        @endif

                        @if (request()->filled('status'))
                            <span class="badge badge-light-warning px-3 py-2">
                                <i class="bi bi-toggle-on me-1"></i>
                                {{ str(request('status'))->headline() }}
                            </span>
                        @endif

                        <a href="{{ route('service-categories.index') }}"
                            class="text-muted fs-8 fw-semibold text-hover-primary ms-1">
                            Clear all
                        </a>
                    </div>
                </div>
            @endif

            <div class="card-body pt-4" id="categories-results">
                @include('pages.apps.service-management.categories._results')
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const searchInput = document.getElementById('category-search-input');
                const form = searchInput?.closest('form');
                const resultsContainer = document.getElementById('categories-results');
                if (!searchInput || !form || !resultsContainer) {
                    return;
                }

                const spinner = document.getElementById('category-search-spinner');
                let debounceTimer = null;
                let activeRequest = null;

                const fetchResults = (url) => {
                    spinner?.classList.remove('d-none');

                    activeRequest?.abort();
                    const controller = new AbortController();
                    activeRequest = controller;

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            signal: controller.signal,
                        })
                        .then((response) => response.text())
                        .then((html) => {
                            resultsContainer.innerHTML = html;
                            history.replaceState(null, '', url);
                        })
                        .catch((error) => {
                            if (error.name !== 'AbortError') {
                                resultsContainer.innerHTML = '';
                            }
                        })
                        .finally(() => {
                            spinner?.classList.add('d-none');
                        });
                };

                searchInput.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        const params = new URLSearchParams(new FormData(form));
                        fetchResults(`${form.action}?${params.toString()}`);
                    }, 400);
                });
            });
        </script>
    @endpush
</x-default-layout>
