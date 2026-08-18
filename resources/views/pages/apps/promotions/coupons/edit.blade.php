<x-default-layout>
    @section('title') Edit Coupon @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.coupons.edit', $coupon) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <form method="POST" action="{{ route('promotions.coupons.update', $coupon) }}" class="card border-0 shadow-sm">
            @method('PUT')
            <div class="card-body p-8">@include('pages/apps.promotions.coupons._form')</div>
        </form>
    </div>
</x-default-layout>
