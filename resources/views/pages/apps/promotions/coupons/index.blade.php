<x-default-layout>
    @section('title') Coupons @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.coupons.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">Coupons</h3>
                @can('create', \App\Models\PromotionCoupon::class)
                    <a href="{{ route('promotions.coupons.create') }}" class="btn btn-primary btn-sm">Create Coupon</a>
                @endcan
            </div>
            <div class="card-body pt-0">
                <form class="row g-3 mb-6">
                    <div class="col-md-5"><input name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Search coupons"></div>
                    <div class="col-md-3"><select name="status" class="form-select form-select-solid"><option value="">All statuses</option>@foreach (\App\Models\PromotionCoupon::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button class="btn btn-light-primary w-100">Filter</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Code</th><th>Promotion</th><th>Period</th><th>Usage</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($coupons as $coupon)
                                <tr>
                                    <td class="fw-bold">{{ $coupon->code }}</td>
                                    <td>{{ $coupon->promotion?->name }}</td>
                                    <td>{{ $coupon->starts_at?->format('M d, Y') ?? 'Now' }} - {{ $coupon->expires_at?->format('M d, Y') ?? 'No end' }}</td>
                                    <td>{{ number_format($coupon->usage_count) }}{{ $coupon->usage_limit ? ' / ' . number_format($coupon->usage_limit) : '' }}</td>
                                    <td><span class="badge badge-light-{{ $coupon->status === 'active' ? 'success' : 'secondary' }}">{{ $coupon->status_label }}</span></td>
                                    <td class="text-end">@can('update', $coupon)<a href="{{ route('promotions.coupons.edit', $coupon) }}" class="btn btn-sm btn-light-primary">Edit</a>@endcan</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">No coupons configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
