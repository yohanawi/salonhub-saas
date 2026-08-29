<x-default-layout>
    @section('title') Promotions & Discounts @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        <div class="card border-0 shadow-sm mb-8 overflow-hidden">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-60px me-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-stars text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-primary fw-bold mb-3">Promotion Studio</div>
                            <h1 class="fw-bolder text-gray-900 mb-2">Promotions & Discounts</h1>
                            <div class="text-gray-600 fs-6">Track live offers, coupons, and discount performance in one clean workspace.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('promotions.promotions.index') }}" class="btn btn-light-primary">
                            <i class="bi bi-megaphone me-2"></i>Promotions
                        </a>
                        <a href="{{ route('promotions.coupons.index') }}" class="btn btn-primary">
                            <i class="bi bi-ticket-perforated me-2"></i>Coupons
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-broadcast text-success fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Active Promotions</div>
                            <div class="fs-2hx fw-bolder text-gray-900">{{ number_format($activePromotions) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-clock-history text-info fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Scheduled</div>
                            <div class="fs-2hx fw-bolder text-gray-900">{{ number_format($scheduledPromotions) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-ticket-perforated text-warning fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Active Coupons</div>
                            <div class="fs-2hx fw-bolder text-gray-900">{{ number_format($coupons) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-danger">
                                <i class="bi bi-cash-coin text-danger fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Discount Given</div>
                            <div class="fs-3 fw-bolder text-gray-900">LKR {{ number_format((float) $discountGiven, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 py-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-activity text-primary fs-3"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0">Recent Promotion Usage</h3>
                                    <div class="text-muted fs-7">Latest redeemed discounts and invoice links</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Promotion</th>
                                        <th>Customer</th>
                                        <th>Invoice</th>
                                        <th class="text-end">Discount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentUsages as $usage)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-gray-900">{{ $usage->promotion?->name ?? 'Promotion removed' }}</div>
                                                <div class="text-muted fs-8">{{ $usage->used_at?->format('M d, Y h:i A') ?? '-' }}</div>
                                            </td>
                                            <td class="text-gray-700">{{ $usage->customer?->full_name ?? '-' }}</td>
                                            <td>
                                                <span class="badge badge-light">{{ $usage->invoice?->invoice_number ?? '-' }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-gray-900">LKR {{ number_format((float) $usage->discount_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-12">
                                                <div class="symbol symbol-60px mx-auto mb-4">
                                                    <div class="symbol-label bg-light">
                                                        <i class="bi bi-ticket-detailed text-muted fs-1"></i>
                                                    </div>
                                                </div>
                                                <div class="fw-bold text-gray-800">No usage recorded yet.</div>
                                                <div class="text-muted fs-7">Redeemed promotions will appear here.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 py-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-trophy text-success fs-3"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0">Top Promotions</h3>
                                    <div class="text-muted fs-7">Most used offers by redemption count</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($topPromotions as $promotion)
                            <div class="d-flex align-items-center justify-content-between border-bottom border-gray-200 py-4">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-light-primary">
                                            <i class="bi bi-percent text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-900">{{ $promotion->name }}</div>
                                        <div class="text-muted fs-8">{{ $promotion->discount_label }}</div>
                                    </div>
                                </div>
                                <span class="badge badge-light-primary fw-bold">{{ number_format($promotion->usages_count) }} uses</span>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="symbol symbol-60px mx-auto mb-4">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-graph-up-arrow text-muted fs-1"></i>
                                    </div>
                                </div>
                                <div class="fw-bold text-gray-800">No promotion performance data yet.</div>
                                <div class="text-muted fs-7">Performance will build as customers redeem offers.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
