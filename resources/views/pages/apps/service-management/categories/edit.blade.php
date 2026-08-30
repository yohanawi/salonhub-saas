<x-default-layout>

    @section('title')
        Edit Service Category
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('service-categories.edit', $category) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('service-categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('pages/apps.service-management.categories._form')
        </form>
    </div>

</x-default-layout>
