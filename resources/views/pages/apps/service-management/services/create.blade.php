<x-default-layout>

    @section('title')
        Add Service
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('services.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('services.store') }}">
            @csrf
            @include('pages/apps.service-management.services._form')
        </form>
    </div>

</x-default-layout>
