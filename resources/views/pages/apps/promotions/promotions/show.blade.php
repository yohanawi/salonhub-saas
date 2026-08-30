<x-default-layout>
    @section('title') {{ $promotion->name }} @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.promotions.show', $promotion) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        @php
            $statusColor = match ($promotion->status) {
                \App\Models\Promotion::STATUS_ACTIVE => 'success',
                \App\Models\Promotion::STATUS_DRAFT => 'info',
                \App\Models\Promotion::STATUS_INACTIVE => 'warning',
                default => 'secondary',
            };
            $usedUsages = $promotion->usages->where('status', 'used');
        @endphp

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-60px me-5">
                            <div class="symbol-label bg-light-{{ $statusColor }}">
                                <i class="bi bi-percent text-{{ $statusColor }} fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge badge-light-{{ $statusColor }}">{{ $promotion->lifecycle_status }}</span>
                                <span class="badge badge-light-primary">{{ $promotion->discount_label }}</span>
                                <span class="badge badge-light">{{ str($promotion->target_scope)->headline() }}</span>
                            </div>
                            <h1 class="fw-bolder text-gray-900 mb-2">{{ $promotion->name }}</h1>
                            <div class="text-muted">
                                {{ $promotion->starts_at?->format('M d, Y h:i A') }} - {{ $promotion->ends_at?->format('M d, Y h:i A') ?? 'No end date' }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-start gap-3">
                        <a href="{{ route('promotions.promotions.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </a>
                        @can('update', $promotion)
                            <a href="{{ route('promotions.promotions.edit', $promotion) }}" class="btn btn-light-primary">
                                <i class="bi bi-pencil-square me-2"></i>Edit
                            </a>
                        @endcan
                        @can('update', $promotion)
                            @if ($promotion->status === \App\Models\Promotion::STATUS_ACTIVE)
                                <form method="POST" action="{{ route('promotions.promotions.deactivate', $promotion) }}">
                                    @csrf
                                    <button class="btn btn-light-warning">
                                        <i class="bi bi-pause-circle me-2"></i>Deactivate
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('promotions.promotions.activate', $promotion) }}">
                                    @csrf
                                    <button class="btn btn-light-success">
                                        <i class="bi bi-play-circle me-2"></i>Activate
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-success"><i class="bi bi-check2-circle text-success fs-2"></i></div></div>
                            <div>
                                <div class="text-muted fw-semibold fs-7 text-uppercase">Redemptions</div>
                                <div class="fs-2 fw-bolder text-gray-900">{{ number_format($usedUsages->count()) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-danger"><i class="bi bi-cash-coin text-danger fs-2"></i></div></div>
                            <div>
                                <div class="text-muted fw-semibold fs-7 text-uppercase">Discount Given</div>
                                <div class="fs-4 fw-bolder text-gray-900">LKR {{ number_format((float) $usedUsages->sum('discount_amount'), 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-warning"><i class="bi bi-ticket-perforated text-warning fs-2"></i></div></div>
                            <div>
                                <div class="text-muted fw-semibold fs-7 text-uppercase">Coupons</div>
                                <div class="fs-2 fw-bolder text-gray-900">{{ number_format($promotion->coupons->count()) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-info"><i class="bi bi-infinity text-info fs-2"></i></div></div>
                            <div>
                                <div class="text-muted fw-semibold fs-7 text-uppercase">Usage Limit</div>
                                <div class="fs-4 fw-bolder text-gray-900">{{ $promotion->usage_limit ? number_format($promotion->usage_limit) : 'Unlimited' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 py-6">
                        <div class="card-title">
                            <div>
                                <h3 class="fw-bold mb-1">Targeting</h3>
                                <div class="text-muted fs-7">Audience, branches, and eligible items</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-5">
                            <div class="col-12">
                                <div class="p-5 bg-light rounded">
                                    <div class="text-muted fs-7 mb-1">Branches</div>
                                    <div class="fw-bold text-gray-900">{{ $promotion->branch_scope === 'all' ? 'All Branches' : ($promotion->branches->pluck('name')->implode(', ') ?: '-') }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-5 bg-light rounded">
                                    <div class="text-muted fs-7 mb-1">Services</div>
                                    <div class="fw-bold text-gray-900">{{ $promotion->services->pluck('name')->implode(', ') ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-5 bg-light rounded">
                                    <div class="text-muted fs-7 mb-1">Products</div>
                                    <div class="fw-bold text-gray-900">{{ $promotion->products->pluck('name')->implode(', ') ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-5 bg-light rounded">
                                    <div class="text-muted fs-7 mb-1">Customers</div>
                                    <div class="fw-bold text-gray-900">{{ str($promotion->customer_scope)->headline() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 py-6">
                        <div class="card-title">
                            <div>
                                <h3 class="fw-bold mb-1">Recent Usage</h3>
                                <div class="text-muted fs-7">Customers who redeemed this promotion</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Customer</th>
                                        <th>Invoice</th>
                                        <th>Branch</th>
                                        <th class="text-end">Discount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($promotion->usages->take(8) as $usage)
                                        <tr>
                                            <td class="fw-bold text-gray-900">{{ $usage->customer?->full_name ?? '-' }}</td>
                                            <td><span class="badge badge-light">{{ $usage->invoice?->invoice_number ?? '-' }}</span></td>
                                            <td class="text-gray-700">{{ $usage->branch?->name ?? '-' }}</td>
                                            <td class="text-end fw-bold">LKR {{ number_format((float) $usage->discount_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-10">No usage recorded for this promotion.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
