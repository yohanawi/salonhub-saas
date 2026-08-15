<x-default-layout>

    @section('title')
        Plan Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.show', $plan) }}
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

        <div class="card border-0 shadow-sm mb-7 overflow-hidden">
            <div class="card-body p-7">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-6">
                    <div class="d-flex align-items-start gap-4">
                        <div class="symbol symbol-70px flex-shrink-0">
                            <div
                                class="symbol-label {{ $plan->is_recommended ? 'bg-light-warning' : 'bg-light-primary' }}">
                                @if ($plan->is_recommended)
                                    <i class="bi bi-star-fill fs-1 text-warning"></i>
                                @else
                                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <h1 class="fw-bold text-gray-900 mb-0">
                                    {{ $plan->name }}
                                </h1>
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
                                @if ($plan->is_recommended)
                                    <span class="badge badge-light-warning">
                                        <i class="bi bi-star-fill me-1"></i>
                                        Recommended
                                    </span>
                                @endif
                            </div>
                            <div class="text-muted fs-7 mb-3">
                                <i class="bi bi-link-45deg me-1"></i>
                                {{ $plan->slug }}
                            </div>
                            <div class="d-flex align-items-baseline gap-2">
                                @if ((float) $plan->price > 0)
                                    <span class="text-muted fs-7">
                                        LKR
                                    </span>
                                    <span class="fw-bold fs-2x text-gray-900">
                                        {{ number_format((float) $plan->price, 2) }}
                                    </span>
                                    <span class="text-muted">
                                        /
                                        {{ str($plan->billing_period)->headline() }}
                                    </span>
                                @else
                                    <span class="badge badge-light-success fs-5 px-4 py-3">
                                        Free Plan
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('plan-management.plans.edit', $plan) }}" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Plan
                        </a>
                        <form method="POST" action="{{ route('plan-management.plans.destroy', $plan) }}"
                            data-plan-archive-form data-plan-name="{{ $plan->name }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light-warning">
                                <i class="bi bi-archive me-2"></i>
                                Archive
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-7">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-cash-stack fs-2 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-semibold">
                                Plan Price
                            </div>
                            <div class="fw-bold fs-3 text-gray-900">
                                @if ((float) $plan->price > 0)
                                    LKR
                                    {{ number_format((float) $plan->price) }}
                                @else
                                    Free
                                @endif
                            </div>
                            <div class="text-muted fs-8">
                                {{ str($plan->billing_period)->headline() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subscribers --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-buildings fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-semibold">
                                Subscribers
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ number_format($plan->subscriptions_count) }}
                            </div>
                            <div class="text-muted fs-8">
                                Current tenants
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trial --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-hourglass-split fs-2 text-info"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-semibold">
                                Trial Period
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $plan->trial_days ?? 0 }}
                            </div>
                            <div class="text-muted fs-8">
                                Days
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-puzzle fs-2 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-semibold">
                                Features
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $plan->enabledFeaturesCount() }}
                            </div>
                            <div class="text-muted fs-8">
                                Enabled features
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-7 mb-7">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-speedometer2 fs-3 text-info"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Usage Limits
                                </h2>
                                <div class="text-muted fs-8">
                                    Tenant resource allowances
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        @php
                            $limits = [
                                [
                                    'field' => 'max_branches',
                                    'label' => 'Branches',
                                    'icon' => 'bi-building',
                                ],
                                [
                                    'field' => 'max_staff',
                                    'label' => 'Staff',
                                    'icon' => 'bi-person-badge',
                                ],
                                [
                                    'field' => 'max_users',
                                    'label' => 'Users',
                                    'icon' => 'bi-people',
                                ],
                                [
                                    'field' => 'max_customers',
                                    'label' => 'Customers',
                                    'icon' => 'bi-person-heart',
                                ],
                            ];
                        @endphp

                        <div class="d-flex flex-column gap-3">
                            @foreach ($limits as $limit)
                                <div
                                    class="limit-item d-flex align-items-center justify-content-between border rounded p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-40px me-3">
                                            <div class="symbol-label bg-light">
                                                <i class="bi {{ $limit['icon'] }} text-gray-700"></i>
                                            </div>
                                        </div>
                                        <span class="fw-semibold text-gray-800">
                                            {{ $limit['label'] }}
                                        </span>
                                    </div>
                                    @if ($plan->{$limit['field']})
                                        <span class="badge badge-light-primary fs-7">
                                            {{ number_format($plan->{$limit['field']}) }}
                                        </span>
                                    @else
                                        <span class="badge badge-light-success">
                                            <i class="bi bi-infinity me-1"></i>
                                            Unlimited
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-puzzle fs-3 text-success"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Plan Features
                                </h2>
                                <div class="text-muted fs-8">
                                    Modules and capabilities included with this plan
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        <div class="row g-4">
                            @foreach (\App\Models\Plan::FEATURE_OPTIONS as $key => $label)
                                @php
                                    $enabled = $plan->hasFeature($key);
                                @endphp
                                <div class="col-md-6">
                                    <div
                                        class="feature-item border rounded p-4 h-100 {{ $enabled ? 'feature-enabled' : '' }}">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px me-3">
                                                <div
                                                    class="symbol-label {{ $enabled ? 'bg-light-success' : 'bg-light' }}">
                                                    @if ($enabled)
                                                        <i class="bi bi-check-lg text-success fs-3"></i>
                                                    @else
                                                        <i class="bi bi-dash-lg text-muted fs-3"></i>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div
                                                    class="fw-semibold {{ $enabled ? 'text-gray-900' : 'text-muted' }}">
                                                    {{ $label }}
                                                </div>
                                            </div>
                                            @if ($enabled)
                                                <span class="badge badge-light-success">
                                                    Enabled
                                                </span>
                                            @else
                                                <span class="badge badge-light">
                                                    Disabled
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-7">
            <div class="card-header border-0 pt-7">
                <div class="card-title d-flex align-items-center gap-3">
                    <div class="symbol symbol-40px">
                        <div class="symbol-label bg-light-warning">
                            <i class="bi bi-sliders fs-3 text-warning"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1">
                            Plan Configuration
                        </h2>
                        <div class="text-muted fs-8">
                            Additional plan settings
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="row g-5">
                    <div class="col-md-3">
                        <div class="configuration-item border rounded p-4 h-100">
                            <div class="text-muted fs-8 mb-2">
                                Billing Period
                            </div>
                            <div class="fw-bold text-gray-900">
                                {{ str($plan->billing_period)->headline() }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="configuration-item border rounded p-4 h-100">
                            <div class="text-muted fs-8 mb-2">
                                Trial
                            </div>
                            <div class="fw-bold text-gray-900">
                                @if ($plan->trial_days)
                                    {{ $plan->trial_days }} Days
                                @else
                                    No Trial
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="configuration-item border rounded p-4 h-100">
                            <div class="text-muted fs-8 mb-2">
                                Sort Order
                            </div>
                            <div class="fw-bold text-gray-900">
                                {{ $plan->sort_order ?? 0 }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="configuration-item border rounded p-4 h-100">
                            <div class="text-muted fs-8 mb-2">
                                Recommendation
                            </div>
                            @if ($plan->is_recommended)
                                <span class="badge badge-light-warning">
                                    <i class="bi bi-star-fill me-1"></i>
                                    Recommended
                                </span>
                            @else
                                <span class="badge badge-light">
                                    Standard
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-7">
                <div class="card-title d-flex align-items-center gap-3">
                    <div class="symbol symbol-40px">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-buildings fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1">
                            Recent Subscriptions
                        </h2>
                        <div class="text-muted fs-8">
                            Tenants currently or recently using
                            {{ $plan->name }}
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('plan-management.subscriptions.index') }}"
                        class="btn btn-sm btn-light-primary">
                        Manage Subscriptions
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-hover fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th class="min-w-220px">
                                    Tenant
                                </th>
                                <th class="min-w-120px">
                                    Status
                                </th>
                                <th class="min-w-140px">
                                    Started
                                </th>
                                <th class="min-w-140px">
                                    Ends
                                </th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($subscriptions as $subscription)
                                @php
                                    $status = $subscription->status;
                                    $statusClass = match ($status) {
                                        'active' => 'badge-light-success',
                                        'trial' => 'badge-light-info',
                                        'past_due' => 'badge-light-warning',
                                        'cancelled', 'canceled', 'inactive' => 'badge-light-danger',
                                        default => 'badge-light',
                                    };
                                @endphp

                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px me-3">
                                                <div class="symbol-label bg-light-primary fw-bold text-primary">
                                                    {{ strtoupper(substr($subscription->tenant?->name ?? '?', 0, 1)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">
                                                    {{ $subscription->tenant?->name ?? 'Unknown tenant' }}
                                                </div>
                                                @if ($subscription->tenant?->email)
                                                    <div class="text-muted fs-8">
                                                        {{ $subscription->tenant->email }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge {{ $statusClass }}">
                                            <span class="bullet bullet-dot me-2"></span>
                                            {{ str($status)->replace('_', ' ')->headline() }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-check text-muted me-2"></i>
                                            {{ $subscription->starts_at?->format('d M Y') ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($subscription->ends_at)
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar-x text-muted me-2"></i>
                                                {{ $subscription->ends_at->format('d M Y') }}
                                            </div>
                                        @else
                                            <span class="badge badge-light-success">
                                                Ongoing
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-15">
                                        <div class="symbol symbol-80px mb-5">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-buildings fs-1 text-primary"></i>
                                            </div>
                                        </div>
                                        <h3 class="fw-bold text-gray-900 mb-2">
                                            No subscriptions yet
                                        </h3>
                                        <div class="text-muted fs-6">
                                            No salon tenants currently use this subscription plan.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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

            .limit-item {
                transition:
                    border-color .2s ease,
                    background-color .2s ease,
                    transform .2s ease;
            }

            .limit-item:hover {
                border-color: var(--bs-primary) !important;
                background: var(--bs-gray-100);
                transform: translateX(2px);
            }

            .feature-item {
                transition:
                    border-color .2s ease,
                    background-color .2s ease,
                    transform .2s ease;
            }

            .feature-item:hover {
                transform: translateY(-2px);
            }

            .feature-enabled {
                border-color:
                    rgba(var(--bs-success-rgb), .35) !important;

                background:
                    rgba(var(--bs-success-rgb), .025);
            }

            .configuration-item {
                transition:
                    border-color .2s ease,
                    background-color .2s ease;
            }

            .configuration-item:hover {
                border-color: var(--bs-primary) !important;
                background: var(--bs-gray-100);
            }

            .table tbody tr {
                transition: background-color .2s ease;
            }

            .table tbody tr:hover {
                background: var(--bs-gray-100);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            KTUtil.onDOMContentLoaded(function() {
                const archiveForm = document.querySelector('[data-plan-archive-form]');

                if (!archiveForm) {
                    return;
                }

                archiveForm.addEventListener('submit', function(event) {
                    event.preventDefault();

                    const planName = archiveForm.getAttribute('data-plan-name') || 'this plan';

                    Swal.fire({
                        title: 'Archive subscription plan?',
                        text: `${planName} will be hidden from new selections. Existing tenant subscriptions will not be removed.`,
                        icon: 'warning',
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: 'Yes, archive it',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            confirmButton: 'btn btn-warning',
                            cancelButton: 'btn btn-light'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            archiveForm.submit();
                        }
                    });
                });
            });
        </script>
    @endpush

</x-default-layout>
