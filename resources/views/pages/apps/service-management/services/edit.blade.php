<x-default-layout>

    @section('title')
        Edit Service
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('services.edit', $service) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('services.update', $service) }}">
            @csrf
            @method('PUT')
            @include('pages/apps.service-management.services._form')
        </form>
    </div>

</x-default-layout>
