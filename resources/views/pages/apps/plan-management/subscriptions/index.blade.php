<x-default-layout>

    @section('title')
        Tenant Subscriptions
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.subscriptions.index') }}
    @endsection

    <div id="kt_app_content_container">
        {{-- Alerts --}}
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center mb-7">
                <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                <div class="fw-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center mb-7">
                <i class="bi bi-exclamation-circle-fill fs-2 me-3"></i>
                <div>
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        {{-- Subscription Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title flex-column align-items-start">
                    <h2 class="fw-bold text-gray-900 mb-1">
                        Subscription Overview
                    </h2>
                    <div class="text-muted fs-7">
                        Showing {{ $tenants->firstItem() ?? 0 }}
                        -
                        {{ $tenants->lastItem() ?? 0 }}
                        of {{ $tenants->total() }} tenants
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-hover fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-220px">
                                    Salon
                                </th>
                                <th class="min-w-180px">
                                    Current Plan
                                </th>
                                <th class="min-w-280px">
                                    Usage
                                </th>
                                <th class="min-w-120px">
                                    Status
                                </th>
                                <th class="text-end min-w-360px">
                                    Subscription Control
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($tenants as $tenant)
                                @php
                                    $subscription = $tenant->subscription;
                                    $currentPlan = $subscription?->plan;
                                    $status = $subscription?->status ?? 'none';
                                    $statusClass = match ($status) {
                                        'active' => 'badge-light-success',
                                        'trial' => 'badge-light-info',
                                        'past_due' => 'badge-light-warning',
                                        'cancelled', 'canceled' => 'badge-light-danger',
                                        'inactive' => 'badge-light-danger',
                                        default => 'badge-light',
                                    };
                                @endphp

                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-primary fw-bold text-primary">
                                                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-gray-900">
                                                    {{ $tenant->name }}
                                                </span>
                                                <span class="text-muted fs-7">
                                                    {{ $tenant->email }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Current Plan --}}
                                    <td>
                                        @if ($currentPlan)
                                            <div class="d-flex flex-column gap-2">
                                                <div>
                                                    <span class="badge badge-light-primary fs-7">
                                                        <i class="bi bi-box-seam me-1"></i>
                                                        {{ $currentPlan->name }}
                                                    </span>
                                                </div>
                                                @if ($subscription?->price !== null)
                                                    <div>
                                                        <span class="fw-bold text-gray-900">
                                                            LKR
                                                            {{ number_format((float) $subscription->price) }}
                                                        </span>
                                                        <span class="text-muted fs-8">
                                                            /
                                                            {{ str($subscription->billing_period)->headline() }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge badge-light-danger">
                                                No Plan
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge badge-light-primary">
                                                <i class="bi bi-building me-1"></i>
                                                Branches:
                                                {{ $tenant->branches_count }}
                                                @if ($currentPlan?->max_branches)
                                                    / {{ $currentPlan->max_branches }}
                                                @endif
                                            </span>
                                            <span class="badge badge-light-info">
                                                <i class="bi bi-person-badge me-1"></i>
                                                Staff:
                                                {{ $tenant->staff_count }}
                                                @if ($currentPlan?->max_staff)
                                                    / {{ $currentPlan->max_staff }}
                                                @endif
                                            </span>
                                            <span class="badge badge-light">
                                                <i class="bi bi-people me-1"></i>
                                                Users:
                                                {{ $tenant->users_count }}
                                                @if ($currentPlan?->max_users)
                                                    / {{ $currentPlan->max_users }}
                                                @endif
                                            </span>
                                            <span class="badge badge-light-success">
                                                <i class="bi bi-person-heart me-1"></i>
                                                Customers:
                                                {{ number_format($tenant->customers_count) }}
                                                @if ($currentPlan?->max_customers)
                                                    /
                                                    {{ number_format($currentPlan->max_customers) }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge {{ $statusClass }}">
                                            <span class="bullet bullet-dot me-2"></span>
                                            {{ str($status)->replace('_', ' ')->headline() }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST"
                                            action="{{ route('plan-management.subscriptions.update', $tenant) }}"
                                            class="d-flex flex-wrap justify-content-end align-items-center gap-2">
                                            @csrf
                                            @method('PATCH')

                                            <select name="plan_id" class="form-select form-select-sm w-180px" required>
                                                @foreach ($plans as $plan)
                                                    <option value="{{ $plan->id }}" @selected($subscription?->plan_id === $plan->id)>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="status" class="form-select form-select-sm w-150px" required>
                                                @foreach ($statuses as $subscriptionStatus)
                                                    <option value="{{ $subscriptionStatus }}"
                                                        @selected($status === $subscriptionStatus)>
                                                        {{ str($subscriptionStatus)->replace('_', ' ')->headline() }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="btn btn-sm btn-primary px-4">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-15">
                                        <div class="symbol symbol-80px mb-5">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-buildings fs-1 text-primary"></i>
                                            </div>
                                        </div>
                                        <h3 class="fw-bold text-gray-900 mb-2">
                                            No tenants found
                                        </h3>
                                        <div class="text-muted fs-6">
                                            Tenant subscriptions will appear here once salons register.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($tenants->hasPages())
                    <div
                        class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 border-top pt-6 mt-3">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-semibold text-gray-800">
                                {{ $tenants->firstItem() }}
                            </span>
                            to
                            <span class="fw-semibold text-gray-800">
                                {{ $tenants->lastItem() }}
                            </span>
                            of
                            <span class="fw-semibold text-gray-800">
                                {{ $tenants->total() }}
                            </span>
                            tenants
                        </div>
                        <div>
                            {{ $tenants->withQueryString()->links() }}
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

            .symbol-label {
                border-radius: 12px;
            }

            .table tbody tr {
                transition: background-color .2s ease;
            }

            .table tbody tr:hover {
                background: var(--bs-gray-100);
            }

            @media (max-width: 767.98px) {

                .w-180px,
                .w-150px {
                    width: 100% !important;
                }

            }
        </style>
    @endpush

</x-default-layout>
