<x-default-layout>
    @section('title') Create Membership Plan @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.membership-plans.create') }} @endsection
    <div id="kt_app_content_container">@include('pages/apps.loyalty-management.partials._alerts')<form method="POST" action="{{ route('loyalty-management.membership-plans.store') }}" class="card border-0 shadow-sm"><div class="card-body p-8">@include('pages/apps.loyalty-management.membership-plans._form')</div></form></div>
</x-default-layout>
