<x-default-layout>
    @section('title') Promotions & Discounts @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <div class="row g-6 mb-8">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Active Promotions</div><div class="fs-2 fw-bold">{{ number_format($activePromotions) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Scheduled</div><div class="fs-2 fw-bold">{{ number_format($scheduledPromotions) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Active Coupons</div><div class="fs-2 fw-bold">{{ number_format($coupons) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Discount Given</div><div class="fs-2 fw-bold">LKR {{ number_format((float) $discountGiven, 2) }}</div></div></div></div>
        </div>

        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Recent Promotion Usage</h3></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Promotion</th><th>Customer</th><th>Invoice</th><th class="text-end">Discount</th></tr></thead>
                                <tbody>
                                    @forelse ($recentUsages as $usage)
                                        <tr>
                                            <td class="fw-bold">{{ $usage->promotion?->name }}</td>
                                            <td>{{ $usage->customer?->full_name ?? '-' }}</td>
                                            <td>{{ $usage->invoice?->invoice_number ?? '-' }}</td>
                                            <td class="text-end">LKR {{ number_format((float) $usage->discount_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-8">No usage recorded yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Top Promotions</h3></div>
                    <div class="card-body pt-0">
                        @forelse ($topPromotions as $promotion)
                            <div class="d-flex justify-content-between border-bottom py-4">
                                <div>
                                    <div class="fw-bold">{{ $promotion->name }}</div>
                                    <div class="text-muted fs-8">{{ $promotion->discount_label }}</div>
                                </div>
                                <span class="badge badge-light-primary">{{ number_format($promotion->usages_count) }} uses</span>
                            </div>
                        @empty
                            <div class="text-muted py-8 text-center">No promotion performance data yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
