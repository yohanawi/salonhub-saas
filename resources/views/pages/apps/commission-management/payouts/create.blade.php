<x-default-layout>
    @section('title') Create Commission Payout @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.payouts.create') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Create Commission Payout</h3></div>
            <div class="card-body">@include('pages/apps.commission-management.payouts._form')</div>
        </div>
    </div>
</x-default-layout>
