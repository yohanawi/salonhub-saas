<x-default-layout>
    @section('title')
        Loyalty & Membership
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.dashboard') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-55px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-stars text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Loyalty & Membership
                                </h3>
                                <span class="badge badge-light-success px-3 py-2">
                                    Rewards Hub
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                Track member activity, point flow, membership health and upcoming renewals.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('loyalty-management.transactions.index') }}" class="btn btn-sm btn-light-primary">
                            <i class="bi bi-arrow-left-right me-2"></i>
                            Point Ledger
                        </a>
                        @can('create', \App\Models\CustomerMembership::class)
                            <a href="{{ route('loyalty-management.memberships.create') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-vcard-fill me-2"></i>
                                Purchase Membership
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            @foreach ([
                ['Active Loyalty Members', $activeLoyaltyMembers, 'primary', 'bi-people-fill'],
                ['Points Issued', $pointsIssued, 'success', 'bi-plus-circle-fill'],
                ['Points Redeemed', $pointsRedeemed, 'warning', 'bi-arrow-down-circle-fill'],
                ['Active Memberships', $activeMemberships, 'info', 'bi-person-badge-fill'],
            ] as [$label, $value, $color, $icon])
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between gap-4">
                            <div>
                                <div class="text-muted fs-7 fw-semibold mb-2">
                                    {{ $label }}
                                </div>
                                <div class="fw-bolder fs-2 text-gray-900">
                                    {{ number_format((float) $value) }}
                                </div>
                            </div>
                            <div class="symbol symbol-50px">
                                <div class="symbol-label bg-light-{{ $color }}">
                                    <i class="bi {{ $icon }} text-{{ $color }} fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-clock-history text-primary fs-4"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0">
                                Recent Point Transactions
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        @forelse ($recentTransactions as $transaction)
                            <div class="d-flex align-items-center justify-content-between gap-4 border-bottom border-gray-200 py-4">
                                <div class="d-flex align-items-center gap-4">
                                    <div class="symbol symbol-40px">
                                        <div class="symbol-label bg-light-{{ $transaction->points >= 0 ? 'success' : 'danger' }}">
                                            <i class="bi {{ $transaction->points >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-left' }} text-{{ $transaction->points >= 0 ? 'success' : 'danger' }}"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $transaction->customer?->full_name ?? 'Unknown Customer' }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            {{ $transaction->description ?: $transaction->type_label }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold {{ $transaction->points >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                                    </div>
                                    <div class="text-muted fs-8">
                                        Balance {{ number_format($transaction->balance_after) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light-primary rounded-circle">
                                        <i class="bi bi-stars text-primary fs-1"></i>
                                    </div>
                                </div>
                                <div class="fw-bold text-gray-900 mb-1">
                                    No point transactions yet
                                </div>
                                <div class="text-muted">
                                    Loyalty activity will appear here when points are earned, redeemed or adjusted.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-calendar2-week text-warning fs-4"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0">
                                Memberships Expiring Soon
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        @forelse ($expiringMemberships as $membership)
                            <div class="d-flex align-items-center justify-content-between gap-4 border-bottom border-gray-200 py-4">
                                <div>
                                    <a href="{{ route('loyalty-management.memberships.show', $membership) }}"
                                        class="fw-bold text-gray-900 text-hover-primary">
                                        {{ $membership->membership_number }}
                                    </a>
                                    <div class="text-muted fs-8">
                                        {{ $membership->customer?->full_name }} - {{ $membership->plan?->name }}
                                    </div>
                                </div>
                                <span class="badge badge-light-warning px-3 py-2">
                                    {{ $membership->end_date?->format('M d, Y') }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light-success rounded-circle">
                                        <i class="bi bi-shield-check text-success fs-1"></i>
                                    </div>
                                </div>
                                <div class="fw-bold text-gray-900 mb-1">
                                    No memberships expiring soon
                                </div>
                                <div class="text-muted">
                                    Active memberships are clear for the next 30 days.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
