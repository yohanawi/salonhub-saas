<x-default-layout>
    @section('title') Create Loyalty Program @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.programs.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <form method="POST" action="{{ route('loyalty-management.programs.store') }}" class="card border-0 shadow-sm"><div class="card-body p-8">@include('pages/apps.loyalty-management.programs._form')</div></form>
    </div>
</x-default-layout>
