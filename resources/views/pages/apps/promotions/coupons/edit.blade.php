<x-default-layout>
    @section('title') Edit Coupon @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.coupons.edit', $coupon) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        <form method="POST" action="{{ route('promotions.coupons.update', $coupon) }}" class="card border-0 shadow-sm">
            @method('PUT')
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-pencil-square text-warning fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1">Edit Coupon</h3>
                            <div class="text-muted fs-7">{{ $coupon->code }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('promotions.coupons.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body p-8 pt-0">
                @include('pages/apps.promotions.coupons._form')
            </div>
        </form>
    </div>
</x-default-layout>
