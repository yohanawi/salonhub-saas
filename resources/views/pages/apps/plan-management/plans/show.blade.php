<x-default-layout>

    @section('title')
        {{ $plan->name }} Plan Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.show', $plan) }}
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

        <div class="card border-0 shadow-sm mb-8 overflow-hidden">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-8">
                    <div class="d-flex flex-column flex-md-row align-items-md-start gap-6">
                        <div class="symbol symbol-80px flex-shrink-0">
                            <div
                                class="symbol-label {{ $plan->is_recommended ? 'bg-light-warning' : 'bg-light-primary' }} rounded-4">
                                @if ($plan->is_recommended)
                                    <i class="bi bi-star-fill fs-1 text-warning"></i>
                                @else
                                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                                @endif
                            </div>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $plan->name }}
                                </h3>
                                @if ($plan->is_active)
                                    <span class="badge badge-light-success px-3 py-2">
                                        <i class="bi bi-circle-fill fs-9 me-2"></i>
                                        Active
                                    </span>
                                @else
                                    <span class="badge badge-light-danger px-3 py-2">
                                        <i class="bi bi-circle-fill fs-9 me-2"></i>
                                        Inactive
                                    </span>
                                @endif
                                @if ($plan->is_recommended)
                                    <span class="badge badge-light-warning px-3 py-2">
                                        <i class="bi bi-star-fill me-1"></i>
                                        Recommended
                                    </span>
                                @endif
                            </div>

                            @if ((float) $plan->price > 0)
                                <div class="d-flex flex-wrap align-items-baseline gap-2">
                                    <span class="text-muted fs-7">
                                        LKR
                                    </span>
                                    <span class="fw-bolder fs-2x text-gray-900">
                                        {{ number_format((float) $plan->price, 2) }}
                                    </span>
                                    <span class="text-muted">
                                        /
                                        {{ str($plan->billing_period)->headline() }}
                                    </span>
                                </div>
                            @else
                                <span class="badge badge-light-success fs-7 px-4 py-2">
                                    <i class="bi bi-gift me-2"></i>
                                    Free Plan
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <a href="{{ route('plan-management.plans.edit', $plan) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Plan
                        </a>
                        <form method="POST" action="{{ route('plan-management.plans.destroy', $plan) }}"
                            data-plan-archive-form data-plan-name="{{ $plan->name }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light-warning btn-sm">
                                <i class="bi bi-archive me-2"></i>
                                Archive
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body px-6 py-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-cash-stack fs-3 text-success"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-success">
                                Pricing
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                            Plan Price
                        </div>
                        <div class="fw-bolder fs-4 text-gray-900">
                            @if ((float) $plan->price > 0)
                                LKR {{ number_format((float) $plan->price, 2) }}
                            @else
                                Free
                            @endif
                        </div>
                        <div class="text-muted fs-8 mt-1">
                            {{ str($plan->billing_period)->headline() }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subscribers --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body px-6 py-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-buildings fs-3 text-primary"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-primary">
                                Tenants
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">
                            Subscribers
                        </div>
                        <div class="fw-bolder fs-2x text-gray-900">
                            {{ number_format($plan->subscriptions_count) }}
                        </div>
                        <div class="text-muted fs-8 mt-1">
                            Current subscriptions
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trial --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body px-6 py-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-hourglass-split fs-3 text-info"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-info">
                                Trial
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">
                            Trial Period
                        </div>
                        <div class="fw-bolder fs-2x text-gray-900">
                            {{ $plan->trial_days ?? 0 }}
                        </div>
                        <div class="text-muted fs-8 mt-1">
                            Days
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body px-6 py-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-puzzle fs-3 text-warning"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-warning">
                                Modules
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">
                            Enabled Features
                        </div>
                        <div class="fw-bolder fs-2x text-gray-900">
                            {{ $plan->enabledFeaturesCount() }}
                        </div>
                        <div class="text-muted fs-8 mt-1">
                            Active capabilities
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8 mb-8">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-speedometer2 fs-3 text-info"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Usage Limits
                                </h2>
                                <div class="text-muted fs-8">
                                    Tenant resource allowances.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
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

                        @foreach ($limits as $limit)
                            <div
                                class="d-flex align-items-center justify-content-between py-4 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px me-4">
                                        <div class="symbol-label bg-light">
                                            <i class="bi {{ $limit['icon'] }} text-gray-700"></i>
                                        </div>
                                    </div>
                                    <span class="fw-semibold text-gray-900">
                                        {{ $limit['label'] }}
                                    </span>
                                </div>
                                @if ($plan->{$limit['field']})
                                    <span class="badge badge-light-primary px-3 py-2">
                                        {{ number_format($plan->{$limit['field']}) }}
                                    </span>
                                @else
                                    <span class="badge badge-light-success px-3 py-2">
                                        <i class="bi bi-infinity me-1"></i>
                                        Unlimited
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-sliders fs-3 text-warning"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Plan Configuration
                                </h2>
                                <div class="text-muted fs-8">
                                    Additional subscription plan settings.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        {{-- Billing --}}
                        <div class="rounded-4 bg-light-primary p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-calendar3 text-primary"></i>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted fs-8">
                                        Billing Period
                                    </div>
                                    <div class="fw-bold text-gray-900 fs-6">
                                        {{ str($plan->billing_period)->headline() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Trial --}}
                        <div class="rounded-4 bg-light-info p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-hourglass text-info"></i>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted fs-8">
                                        Trial
                                    </div>
                                    <div class="fw-bold text-gray-900 fs-6">
                                        @if ($plan->trial_days)
                                            {{ $plan->trial_days }} Days
                                        @else
                                            No Trial
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Sort Order --}}
                        <div class="rounded-4 bg-light p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-sort-numeric-down text-gray-700"></i>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted fs-8">
                                        Sort Order
                                    </div>
                                    <div class="fw-bold text-gray-900 fs-6">
                                        {{ $plan->sort_order ?? 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Recommendation --}}
                        <div class="rounded-4 bg-light-warning p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-star text-warning"></i>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted fs-8">
                                        Recommendation
                                    </div>
                                    @if ($plan->is_recommended)
                                        <span class="badge badge-light-warning px-3 py-2">
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
            </div>

            {{-- Plan Features --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-puzzle fs-3 text-success"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Plan Features
                                </h2>
                                <div class="text-muted fs-8">
                                    Modules and capabilities included with this plan.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row g-4">
                            @foreach (\App\Models\Plan::FEATURE_OPTIONS as $key => $label)
                                @php
                                    $enabled = $plan->hasFeature($key);
                                @endphp
                                <div class="col-md-6">
                                    <div
                                        class="rounded-4 border {{ $enabled ? 'border-success bg-light-success' : 'border-gray-300 bg-light' }} p-4 h-100">
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="symbol symbol-30px flex-shrink-0">
                                                <div class="symbol-label {{ $enabled ? 'bg-white' : 'bg-light' }}">
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

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-buildings fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Recent Subscriptions
                        </h2>
                        <div class="text-muted fs-8">
                            Tenants currently or recently using {{ $plan->name }}.
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
            <div class="card-body pt-4">
                @if ($subscriptions->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
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
                                @foreach ($subscriptions as $subscription)
                                    @php
                                        $status = $subscription->status;
                                        $statusClass = match ($status) {
                                            'active' => 'success',
                                            'trialing' => 'info',
                                            'past_due' => 'warning',
                                            'cancelled', 'canceled', 'inactive' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-4">
                                                    <div class="symbol-label bg-light-primary text-primary fw-bold">
                                                        {{ strtoupper(substr($subscription->tenant?->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-gray-900 mb-1">
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
                                            <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                                <i class="bi bi-circle-fill fs-9 me-2"></i>
                                                {{ str($status)->replace('_', ' ')->headline() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light-success">
                                                        <i class="bi bi-calendar-check text-success"></i>
                                                    </div>
                                                </div>
                                                <span>
                                                    {{ $subscription->starts_at?->format('d M Y') ?? '-' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($subscription->ends_at)
                                                <div class="d-flex align-items-center">
                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-danger">
                                                            <i class="bi bi-calendar-x text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <span>
                                                        {{ $subscription->ends_at->format('d M Y') }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="badge badge-light-success px-3 py-2">
                                                    <i class="bi bi-infinity me-1"></i>
                                                    Ongoing
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-15">
                        <div class="symbol symbol-80px mb-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-buildings fs-1 text-primary"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-gray-900 mb-2">
                            No subscriptions yet
                        </h3>
                        <div class="text-muted fs-6 mx-auto">
                            No salon tenants currently use this subscription plan.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

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
