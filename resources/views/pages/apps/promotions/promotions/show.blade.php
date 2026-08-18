<x-default-layout>
    @section('title') {{ $promotion->name }} @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.promotions.show', $promotion) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 d-flex justify-content-between flex-wrap gap-4">
                <div>
                    <h1 class="fw-bolder mb-2">{{ $promotion->name }}</h1>
                    <div class="text-muted">{{ $promotion->discount_label }} · {{ str($promotion->target_scope)->headline() }} · {{ $promotion->lifecycle_status }}</div>
                </div>
                <div class="d-flex gap-2">
                    @can('update', $promotion)<a href="{{ route('promotions.promotions.edit', $promotion) }}" class="btn btn-light-primary">Edit</a>@endcan
                    @can('update', $promotion)
                        @if ($promotion->status === \App\Models\Promotion::STATUS_ACTIVE)
                            <form method="POST" action="{{ route('promotions.promotions.deactivate', $promotion) }}">@csrf<button class="btn btn-light-warning">Deactivate</button></form>
                        @else
                            <form method="POST" action="{{ route('promotions.promotions.activate', $promotion) }}">@csrf<button class="btn btn-light-success">Activate</button></form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
        <div class="row g-6 mb-8">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Redemptions</div><div class="fs-2 fw-bold">{{ number_format($promotion->usages->where('status', 'used')->count()) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Discount Given</div><div class="fs-2 fw-bold">LKR {{ number_format((float) $promotion->usages->where('status', 'used')->sum('discount_amount'), 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Coupons</div><div class="fs-2 fw-bold">{{ number_format($promotion->coupons->count()) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Usage Limit</div><div class="fs-2 fw-bold">{{ $promotion->usage_limit ? number_format($promotion->usage_limit) : 'Unlimited' }}</div></div></div></div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Targeting</h3></div>
            <div class="card-body pt-0">
                <div class="row g-6">
                    <div class="col-md-3"><div class="text-muted">Branches</div><div class="fw-bold">{{ $promotion->branch_scope === 'all' ? 'All Branches' : $promotion->branches->pluck('name')->implode(', ') }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Services</div><div class="fw-bold">{{ $promotion->services->pluck('name')->implode(', ') ?: '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Products</div><div class="fw-bold">{{ $promotion->products->pluck('name')->implode(', ') ?: '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Customers</div><div class="fw-bold">{{ str($promotion->customer_scope)->headline() }}</div></div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
