<x-default-layout>

    @section('title')
        Add Service Category
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('service-categories.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('service-categories.store') }}">
            @csrf
            @include('pages/apps.service-management.categories._form')
        </form>
    </div>

</x-default-layout>
