<x-default-layout>
    @section('title')
        Loyalty Reports
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.reports.index') }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex align-items-start gap-5">
                    <div class="symbol symbol-55px flex-shrink-0">
                        <div class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-graph-up-arrow text-primary fs-1"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h3 class="fw-bolder text-gray-900 mb-0">
                                Loyalty Reports
                            </h3>
                            <span class="badge badge-light-info px-3 py-2">
                                Performance View
                            </span>
                        </div>
                        <div class="text-muted fs-6">
                            Review rewards movement, membership revenue, benefit discounts and top point balances.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            @foreach([
                ['Points Earned', $pointsEarned, 'success', 'bi-plus-circle-fill'],
                ['Points Redeemed', $pointsRedeemed, 'warning', 'bi-arrow-down-circle-fill'],
                ['Points Expired', $pointsExpired, 'danger', 'bi-hourglass-bottom'],
                ['Outstanding Points', $outstandingPoints, 'primary', 'bi-stars'],
                ['Membership Revenue', 'LKR ' . number_format((float) $membershipRevenue, 2), 'info', 'bi-cash-stack'],
                ['Benefit Discounts', 'LKR ' . number_format((float) $benefitDiscounts, 2), 'success', 'bi-percent'],
            ] as [$label, $value, $color, $icon])
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between gap-4">
                            <div>
                                <div class="text-muted fs-7 fw-semibold mb-2">
                                    {{ $label }}
                                </div>
                                <div class="fw-bolder fs-2 text-gray-900">
                                    {{ is_numeric($value) ? number_format((float) $value) : $value }}
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

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-7">
                <div class="card-title">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-trophy-fill text-primary fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0">
                        Top Loyalty Balances
                    </h3>
                </div>
            </div>
            <div class="card-body pt-3">
                @forelse($topBalances as $account)
                    <div class="d-flex align-items-center justify-content-between gap-4 border-bottom border-gray-200 py-4">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-person-fill text-success"></i>
                                </div>
                            </div>
                            <span class="fw-bold text-gray-900">
                                {{ $account->customer?->full_name ?? 'Unknown Customer' }}
                            </span>
                        </div>
                        <span class="badge badge-light-primary px-3 py-2">
                            {{ number_format($account->available_points) }} points
                        </span>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="symbol symbol-70px mb-5">
                            <div class="symbol-label bg-light-primary rounded-circle">
                                <i class="bi bi-stars text-primary fs-1"></i>
                            </div>
                        </div>
                        <div class="fw-bold text-gray-900 mb-1">
                            No loyalty balances yet
                        </div>
                        <div class="text-muted">
                            Customer balances will appear here after points are awarded.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-default-layout>
