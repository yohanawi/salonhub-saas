<x-default-layout>

    @section('title')
        Tenant Subscriptions
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.subscriptions.index') }}
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
                        <i class="bi bi-exclamation-circle-fill fs-2 text-danger"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Subscription update failed
                    </div>
                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-50px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-credit-card-2-front fs-1 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Tenant Subscriptions
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    SaaS Billing
                                </span>
                            </div>
                            <div class="text-muted fs-7">
                                Manage salon plans, subscription status and tenant usage from one place.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end">
                            <div class="text-muted fs-8 fw-semibold text-uppercase">
                                Total Tenants
                            </div>
                            <div class="fw-bolder text-gray-900 fs-2">
                                {{ number_format($tenants->total()) }}
                            </div>
                        </div>
                        <div class="symbol symbol-45px">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-buildings text-success fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-diagram-3 fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Subscription Overview
                        </h2>
                        <div class="text-muted fs-8">
                            Showing {{ $tenants->firstItem() ?? 0 }}
                            -
                            {{ $tenants->lastItem() ?? 0 }}
                            of {{ number_format($tenants->total()) }} tenants
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                @if ($tenants->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-230px">
                                        Salon
                                    </th>
                                    <th class="min-w-180px">
                                        Current Plan
                                    </th>
                                    <th class="min-w-320px">
                                        Usage
                                    </th>
                                    <th class="min-w-130px">
                                        Status
                                    </th>
                                    <th class="text-end min-w-380px">
                                        Subscription Control
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="text-gray-700 fw-semibold">
                                @foreach ($tenants as $tenant)
                                    @php
                                        $subscription = $tenant->subscription;
                                        $currentPlan = $subscription?->plan;
                                        $status = $subscription?->status ?? 'none';

                                        $statusConfig = match ($status) {
                                            'active' => [
                                                'class' => 'success',
                                                'icon' => 'bi-check-circle-fill',
                                            ],

                                            'trialing' => [
                                                'class' => 'info',
                                                'icon' => 'bi-hourglass-split',
                                            ],

                                            'past_due' => [
                                                'class' => 'warning',
                                                'icon' => 'bi-exclamation-circle-fill',
                                            ],

                                            'cancelled', 'canceled', 'inactive' => [
                                                'class' => 'danger',
                                                'icon' => 'bi-x-circle-fill',
                                            ],

                                            default => [
                                                'class' => 'secondary',
                                                'icon' => 'bi-dash-circle',
                                            ],
                                        };

                                        $tenantInitial = strtoupper(substr($tenant->name, 0, 1));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-4 flex-shrink-0">
                                                    <div class="symbol-label bg-light-primary text-primary fw-bolder">
                                                        {{ $tenantInitial }}
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-gray-900 fs-6">
                                                        {{ $tenant->name }}
                                                    </span>
                                                    <span class="text-muted fs-8 mt-1">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        {{ $tenant->email }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($currentPlan)
                                                <div class="d-flex flex-column align-items-start gap-2">
                                                    <span class="badge badge-light-primary px-3 py-2">
                                                        <i class="bi bi-box-seam me-1"></i>
                                                        {{ $currentPlan->name }}
                                                    </span>
                                                    @if ($subscription?->price !== null)
                                                        <div>
                                                            <span class="fw-bold text-gray-900">
                                                                LKR
                                                                {{ number_format((float) $subscription->price, 2) }}
                                                            </span>
                                                            <span class="text-muted fs-8">
                                                                /
                                                                {{ str($subscription->billing_period)->headline() }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="badge badge-light-danger px-3 py-2">
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                    No Plan
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="rounded-3 bg-light-primary p-3">
                                                        <div
                                                            class="d-flex align-items-center justify-content-between gap-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-building text-primary me-2"></i>
                                                                <span class="text-muted fs-8">
                                                                    Branches
                                                                </span>
                                                            </div>
                                                            <span class="fw-bold text-gray-900">
                                                                {{ $tenant->branches_count }}
                                                                @if ($currentPlan?->max_branches)
                                                                    / {{ $currentPlan->max_branches }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="rounded-3 bg-light-info p-3">
                                                        <div
                                                            class="d-flex align-items-center justify-content-between gap-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-person-badge text-info me-2"></i>
                                                                <span class="text-muted fs-8">
                                                                    Staff
                                                                </span>
                                                            </div>
                                                            <span class="fw-bold text-gray-900">
                                                                {{ $tenant->staff_count }}
                                                                @if ($currentPlan?->max_staff)
                                                                    / {{ $currentPlan->max_staff }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="rounded-3 bg-light p-3">
                                                        <div
                                                            class="d-flex align-items-center justify-content-between gap-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-people text-gray-600 me-2"></i>
                                                                <span class="text-muted fs-8">
                                                                    Users
                                                                </span>
                                                            </div>
                                                            <span class="fw-bold text-gray-900">
                                                                {{ $tenant->users_count }}
                                                                @if ($currentPlan?->max_users)
                                                                    / {{ $currentPlan->max_users }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="rounded-3 bg-light-success p-3">
                                                        <div
                                                            class="d-flex align-items-center justify-content-between gap-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-person-heart text-success me-2"></i>
                                                                <span class="text-muted fs-8">
                                                                    Customers
                                                                </span>
                                                            </div>
                                                            <span class="fw-bold text-gray-900">
                                                                {{ number_format($tenant->customers_count) }}
                                                                @if ($currentPlan?->max_customers)
                                                                    /
                                                                    {{ number_format($currentPlan->max_customers) }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $statusConfig['class'] }} px-3 py-2">
                                                <i class="bi {{ $statusConfig['icon'] }} me-1"></i>
                                                {{ str($status)->replace('_', ' ')->headline() }}
                                            </span>
                                        </td>

                                        <td>
                                            <form method="POST"
                                                action="{{ route('plan-management.subscriptions.update', $tenant) }}"
                                                class="d-flex flex-column flex-lg-row justify-content-end align-items-lg-center gap-2"
                                                data-swal-confirm
                                                data-swal-title="Update subscription for {{ $tenant->name }}?"
                                                data-swal-text="This changes their plan and billing status immediately."
                                                data-swal-icon="question"
                                                data-swal-confirm-button="Yes, save changes"
                                                data-swal-cancel-button="Cancel">
                                                @csrf
                                                @method('PATCH')
                                                {{-- Plan --}}
                                                <div class="flex-grow-1">
                                                    <select name="plan_id" class="form-select form-select-sm" required
                                                        data-control="select2" data-hide-search="true">
                                                        @foreach ($plans as $plan)
                                                            <option value="{{ $plan->id }}"
                                                                @selected($subscription?->plan_id === $plan->id)
                                                                @disabled(!$plan->is_active)>
                                                                {{ $plan->name }}
                                                                @if (!$plan->is_active)
                                                                    (Archived)
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                {{-- Status --}}
                                                <div class="flex-grow-1">
                                                    <select name="status" class="form-select form-select-sm"
                                                        data-control="select2" data-hide-search="true" required>
                                                        @foreach ($statuses as $subscriptionStatus)
                                                            <option value="{{ $subscriptionStatus }}"
                                                                @selected($status === $subscriptionStatus)>
                                                                {{ str($subscriptionStatus)->replace('_', ' ')->headline() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                {{-- Save --}}
                                                <button type="submit"
                                                    class="btn btn-sm btn-primary px-4 flex-shrink-0">
                                                    <i class="bi bi-check2-circle me-1"></i>
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($tenants->hasPages())
                        <div
                            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 border-top border-gray-200 pt-6 mt-6">
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
                                    {{ number_format($tenants->total()) }}
                                </span>
                                tenants
                            </div>
                            <div>
                                {{ $tenants->withQueryString()->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-15">
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
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof Swal === 'undefined') {
                    return;
                }

                @if (session('status'))
                    Swal.fire({
                        text: @js(session('status')),
                        icon: 'success',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok, got it',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                    });
                @endif

                @if ($errors->any())
                    Swal.fire({
                        title: 'Subscription update failed',
                        text: @js($errors->first()),
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok, I will fix it',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                    });
                @endif

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
                                confirmButton: 'btn btn-primary',
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
