<x-default-layout>
    @section('title') Add Product Category @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.categories.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.categories.store') }}">
            @include('pages/apps.inventory.categories._form', ['submitLabel' => 'Create Category'])
        </form>
    </div>
</x-default-layout>
