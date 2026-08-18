<x-default-layout>
    @section('title') Edit Loyalty Program @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.programs.edit', $program) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <form method="POST" action="{{ route('loyalty-management.programs.update', $program) }}" class="card border-0 shadow-sm">
            @method('PUT')
            <div class="card-body p-8">@include('pages/apps.loyalty-management.programs._form')</div>
        </form>
    </div>
</x-default-layout>
