<x-default-layout>
    @section('title') Loyalty & Membership @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="row g-6 mb-8">
            @foreach ([
                ['Active Loyalty Members', $activeLoyaltyMembers, 'primary'],
                ['Points Issued', $pointsIssued, 'success'],
                ['Points Redeemed', $pointsRedeemed, 'warning'],
                ['Active Memberships', $activeMemberships, 'info'],
            ] as [$label, $value, $color])
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7 mb-2">{{ $label }}</div>
                            <div class="fw-bolder fs-2 text-{{ $color }}">{{ number_format((float) $value) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Recent Point Transactions</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($recentTransactions as $transaction)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $transaction->customer?->full_name }}</div>
                                    <div class="text-muted fs-8">{{ $transaction->description ?: $transaction->type_label }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold {{ $transaction->points >= 0 ? 'text-success' : 'text-danger' }}">{{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}</div>
                                    <div class="text-muted fs-8">Balance {{ number_format($transaction->balance_after) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-6">No point transactions yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Memberships Expiring Soon</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($expiringMemberships as $membership)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <a href="{{ route('loyalty-management.memberships.show', $membership) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $membership->membership_number }}</a>
                                    <div class="text-muted fs-8">{{ $membership->customer?->full_name }} - {{ $membership->plan?->name }}</div>
                                </div>
                                <span class="badge badge-light-warning align-self-center">{{ $membership->end_date?->format('M d, Y') }}</span>
                            </div>
                        @empty
                            <div class="text-muted py-6">No memberships expiring in the next 30 days.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
