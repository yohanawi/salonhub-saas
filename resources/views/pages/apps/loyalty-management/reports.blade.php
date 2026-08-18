<x-default-layout>
    @section('title') Loyalty Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.reports.index') }} @endsection
    <div id="kt_app_content_container">
        <div class="row g-6 mb-8">
            @foreach([['Points Earned', $pointsEarned], ['Points Redeemed', $pointsRedeemed], ['Points Expired', $pointsExpired], ['Outstanding Points', $outstandingPoints], ['Membership Revenue', 'LKR ' . number_format((float) $membershipRevenue, 2)], ['Benefit Discounts', 'LKR ' . number_format((float) $benefitDiscounts, 2)]] as [$label, $value])
                <div class="col-xl-4 col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted fs-7 mb-2">{{ $label }}</div><div class="fw-bolder fs-2">{{ is_numeric($value) ? number_format((float) $value) : $value }}</div></div></div></div>
            @endforeach
        </div>
        <div class="card border-0 shadow-sm"><div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Top Loyalty Balances</h3></div><div class="card-body pt-3">@forelse($topBalances as $account)<div class="d-flex justify-content-between border-bottom py-3"><span class="fw-bold">{{ $account->customer?->full_name }}</span><span>{{ number_format($account->available_points) }} points</span></div>@empty<div class="text-muted py-6">No loyalty balances yet.</div>@endforelse</div></div>
    </div>
</x-default-layout>
