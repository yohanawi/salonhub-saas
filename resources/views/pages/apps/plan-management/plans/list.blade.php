<x-default-layout>

    @section('title')
        Subscription Plans
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center mb-7">
                <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                <div class="fw-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            {{-- Search / Filters --}}
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <form method="GET" action="{{ route('plan-management.plans.index') }}"
                        class="d-flex align-items-center gap-3">
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                            <input type="text" name="search" id="plan-search-input" value="{{ request('search') }}"
                                class="form-control ps-10 w-250px" placeholder="Search plans..." autocomplete="off">
                            <span id="plan-search-spinner"
                                class="spinner-border spinner-border-sm text-primary position-absolute top-50 translate-middle-y end-0 me-4 d-none"
                                role="status" aria-hidden="true"></span>
                        </div>
                        {{-- Status --}}
                        <select name="status" class="form-select" data-control="select2" data-hide-search="true">
                            <option value="">
                                All Status
                            </option>
                            <option value="active" @selected(request('status') === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')>
                                Inactive
                            </option>
                        </select>

                        <button type="submit" class="btn btn-light-primary d-flex align-items-center">
                            <i class="bi bi-funnel me-1"></i>
                            Filter
                        </button>

                        @if (request()->filled('search') || request()->filled('status'))
                            <a href="{{ route('plan-management.plans.index') }}" class="btn btn-light">
                                <i class="bi bi-x-lg me-1"></i>
                                Clear
                            </a>
                        @endif
                    </form>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-end gap-5 mb-8">
                    <a href="{{ route('plan-management.plans.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add New Plan
                    </a>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-hover fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-200px">
                                    Plan
                                </th>
                                <th class="min-w-150px">
                                    Pricing
                                </th>
                                <th class="min-w-250px">
                                    Usage Limits
                                </th>
                                <th class="min-w-120px">
                                    Features
                                </th>
                                <th class="min-w-110px">
                                    Subscribers
                                </th>
                                <th class="min-w-100px">
                                    Status
                                </th>
                                <th class="text-end min-w-100px">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody id="plans-table-body" class="text-gray-700 fw-semibold">
                            @forelse ($plans as $plan)
                                <tr class="plan-row {{ $plan->is_recommended ? 'recommended-plan-row' : '' }}"
                                    data-plan-search="{{ strtolower($plan->name . ' ' . $plan->slug) }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <div class="d-flex align-items-center flex-wrap gap-2">
                                                    <a href="{{ route('plan-management.plans.show', $plan) }}"
                                                        class="text-gray-900 text-hover-primary fw-bold fs-6">
                                                        {{ $plan->name }}
                                                    </a>
                                                    @if ($plan->is_recommended)
                                                        <span class="badge badge-light-warning">
                                                            <i class="bi bi-star-fill me-1"></i>
                                                            Recommended
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="text-muted fs-7 mt-1">
                                                    {{ $plan->slug }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ((float) $plan->price > 0)
                                            <div class="d-flex align-items-baseline gap-1">
                                                <span class="text-muted fs-8">
                                                    LKR
                                                </span>
                                                <span class="fw-bold fs-4 text-gray-900">
                                                    {{ number_format((float) $plan->price) }}
                                                </span>
                                            </div>
                                            <div class="text-muted fs-8 mt-1">
                                                per {{ str($plan->billing_period)->headline() }}
                                            </div>
                                        @else
                                            <span class="badge badge-light-success fs-7">
                                                Free
                                            </span>
                                            @if ($plan->trial_days)
                                                <div class="text-muted fs-8 mt-2">
                                                    {{ $plan->trial_days }} day trial
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge badge-light-primary">
                                                <i class="bi bi-building me-1"></i>
                                                {{ $plan->max_branches ?? '∞' }} Branches
                                            </span>
                                            <span class="badge badge-light-info">
                                                <i class="bi bi-person-badge me-1"></i>
                                                {{ $plan->max_staff ?? '∞' }} Staff
                                            </span>
                                            <span class="badge badge-light">
                                                <i class="bi bi-people me-1"></i>
                                                {{ $plan->max_users ?? '∞' }} Users
                                            </span>
                                            <span class="badge badge-light-success">
                                                <i class="bi bi-person-heart me-1"></i>
                                                {{ $plan->max_customers ? number_format($plan->max_customers) : '∞' }}
                                                Customers
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        @php
                                            $featureCount = $plan->enabledFeaturesCount();
                                        @endphp
                                        <a href="{{ route('plan-management.plans.show', $plan) }}"
                                            class="d-inline-flex align-items-center gap-2 text-hover-primary">
                                            <div class="symbol symbol-35px">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-puzzle text-primary"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">
                                                    {{ $featureCount }}
                                                </div>
                                                <div class="text-muted fs-8">
                                                    Enabled
                                                </div>
                                            </div>
                                        </a>
                                    </td>

                                    <td>
                                        <a href="{{ route('plan-management.plans.show', $plan) }}"
                                            class="d-inline-flex align-items-center gap-2">
                                            <div class="symbol symbol-35px">
                                                <div class="symbol-label bg-light-info">
                                                    <i class="bi bi-buildings text-info"></i>
                                                </div>
                                            </div>
                                            <span class="fw-bold text-gray-900 text-hover-primary">
                                                {{ number_format($plan->subscriptions_count) }}
                                            </span>
                                        </a>
                                    </td>

                                    <td>
                                        @if ($plan->is_active)
                                            <span class="badge badge-light-success">
                                                <span class="bullet bullet-dot bg-success me-2"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="badge badge-light-danger">
                                                <span class="bullet bullet-dot bg-danger me-2"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    {{-- =========================
                                        Actions
                                    ========================== --}}
                                    <td class="text-end">
                                        <button type="button"
                                            class="btn btn-sm btn-light btn-active-light-primary d-flex align-items-center"
                                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                            Actions
                                            <i class="bi bi-chevron-down fs-8 ms-1"></i>
                                        </button>
                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4"
                                            data-kt-menu="true">
                                            {{-- View --}}
                                            <div class="menu-item px-3">
                                                <a href="{{ route('plan-management.plans.show', $plan) }}"
                                                    class="menu-link px-3">
                                                    <i class="bi bi-eye me-3"></i>
                                                    View Details
                                                </a>
                                            </div>

                                            {{-- Edit --}}
                                            <div class="menu-item px-3">
                                                <a href="{{ route('plan-management.plans.edit', $plan) }}"
                                                    class="menu-link px-3">
                                                    <i class="bi bi-pencil-square me-3"></i>
                                                    Edit Plan
                                                </a>
                                            </div>
                                            <div class="separator my-2"></div>
                                            {{-- Status --}}
                                            <div class="menu-item px-3">
                                                <form method="POST"
                                                    action="{{ route('plan-management.plans.status.update', $plan) }}"
                                                    @if ($plan->is_active)
                                                        data-swal-confirm
                                                        data-swal-title="Deactivate {{ $plan->name }}?"
                                                        data-swal-text="Tenants will no longer be able to select this plan. Existing subscriptions are not affected."
                                                        data-swal-icon="warning"
                                                        data-swal-confirm-button="Yes, deactivate"
                                                        data-swal-cancel-button="Keep active"
                                                    @endif>
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active"
                                                        value="{{ $plan->is_active ? 0 : 1 }}">
                                                    <button type="submit"
                                                        class="menu-link px-3 border-0 bg-transparent w-100 text-start">
                                                        @if ($plan->is_active)
                                                            <i class="bi bi-pause-circle text-warning me-3"></i>
                                                            Deactivate
                                                        @else
                                                            <i class="bi bi-play-circle text-success me-3"></i>
                                                            Activate
                                                        @endif
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-15">
                                        <div class="symbol symbol-80px mb-5">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-box-seam fs-1 text-primary"></i>
                                            </div>
                                        </div>

                                        <h3 class="fw-bold text-gray-900 mb-2">
                                            No subscription plans found
                                        </h3>
                                        <div class="text-muted fs-6 mb-6">
                                            @if (request()->filled('search') || request()->filled('status'))
                                                No plans match your current
                                                search or filter.
                                            @else
                                                Create your first subscription plan for salon tenants.
                                            @endif
                                        </div>

                                        @if (request()->filled('search') || request()->filled('status'))
                                            <a href="{{ route('plan-management.plans.index') }}"
                                                class="btn btn-light-primary">
                                                Clear Filters
                                            </a>
                                        @else
                                            <a href="{{ route('plan-management.plans.create') }}"
                                                class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-2"></i>
                                                Create First Plan
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse

                            @if ($plans->isNotEmpty())
                                <tr id="plan-search-no-match" class="d-none">
                                    <td colspan="7" class="text-center py-15">
                                        <div class="symbol symbol-80px mb-5">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-search fs-1 text-primary"></i>
                                            </div>
                                        </div>

                                        <h3 class="fw-bold text-gray-900 mb-2">
                                            No plans match "<span id="plan-search-no-match-term"></span>"
                                        </h3>
                                        <div class="text-muted fs-6 mb-6">
                                            Try a different name or clear the search.
                                        </div>

                                        <button type="button" id="plan-search-clear" class="btn btn-light-primary">
                                            <i class="bi bi-x-lg me-1"></i>
                                            Clear Search
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($plans->hasPages())
                    <div
                        class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 border-top pt-6 mt-3">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-semibold text-gray-800">
                                {{ $plans->firstItem() }}
                            </span>
                            to
                            <span class="fw-semibold text-gray-800">
                                {{ $plans->lastItem() }}
                            </span>
                            of
                            <span class="fw-semibold text-gray-800">
                                {{ $plans->total() }}
                            </span>
                            plans
                        </div>
                        <div>
                            {{ $plans->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .card {
                border-radius: 14px;
            }

            .recommended-plan-row {
                position: relative;
                background: rgba(var(--bs-warning-rgb), .025);
            }

            .recommended-plan-row>td:first-child {
                border-left: 3px solid var(--bs-warning);
            }

            .table tbody tr {
                transition:
                    background-color .2s ease,
                    transform .2s ease;
            }

            .table tbody tr:hover {
                background-color: var(--bs-gray-100);
            }

            .symbol-label {
                border-radius: 12px;
            }

            .position-relative>.bi-search {
                z-index: 2;
                pointer-events: none;
            }

            .badge {
                white-space: nowrap;
            }

            @media (max-width: 767.98px) {

                .w-250px,
                .w-175px {
                    width: 100% !important;
                }

            }

            .plan-row {
                transition: opacity .15s ease;
            }

            #plan-search-input {
                transition: box-shadow .2s ease;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const searchInput = document.getElementById('plan-search-input');
                if (!searchInput) {
                    return;
                }

                const spinner = document.getElementById('plan-search-spinner');
                const rows = Array.from(document.querySelectorAll('#plans-table-body .plan-row'));
                const noMatchRow = document.getElementById('plan-search-no-match');
                const noMatchTerm = document.getElementById('plan-search-no-match-term');
                const clearBtn = document.getElementById('plan-search-clear');

                let debounceTimer = null;

                const applyFilter = (rawTerm) => {
                    const term = rawTerm.trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const haystack = row.dataset.planSearch || '';
                        const isMatch = term === '' || haystack.includes(term);
                        row.classList.toggle('d-none', !isMatch);
                        if (isMatch) {
                            visibleCount++;
                        }
                    });

                    if (noMatchRow) {
                        const showNoMatch = term !== '' && visibleCount === 0 && rows.length > 0;
                        noMatchRow.classList.toggle('d-none', !showNoMatch);
                        if (showNoMatch && noMatchTerm) {
                            noMatchTerm.textContent = rawTerm.trim();
                        }
                    }

                    spinner?.classList.add('d-none');
                };

                searchInput.addEventListener('input', (event) => {
                    spinner?.classList.remove('d-none');
                    clearTimeout(debounceTimer);
                    const value = event.target.value;
                    debounceTimer = setTimeout(() => applyFilter(value), 180);
                });

                clearBtn?.addEventListener('click', () => {
                    searchInput.value = '';
                    applyFilter('');
                    searchInput.focus();
                });

                if (searchInput.value) {
                    applyFilter(searchInput.value);
                }
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof Swal === 'undefined') {
                    return;
                }

                document.querySelectorAll('form[data-swal-confirm]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (form.dataset.swalSubmitting === 'true') {
                            return;
                        }

                        event.preventDefault();

                        Swal.fire({
                            title: form.dataset.swalTitle || 'Are you sure?',
                            text: form.dataset.swalText || 'This action cannot be undone.',
                            icon: form.dataset.swalIcon || 'warning',
                            showCancelButton: true,
                            buttonsStyling: false,
                            confirmButtonText: form.dataset.swalConfirmButton || 'Yes, continue',
                            cancelButtonText: form.dataset.swalCancelButton || 'Cancel',
                            customClass: {
                                confirmButton: 'btn btn-warning',
                                cancelButton: 'btn btn-light',
                            },
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.dataset.swalSubmitting = 'true';
                                form.submit();
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

</x-default-layout>
