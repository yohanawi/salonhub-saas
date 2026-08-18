<x-default-layout>
    @section('title') Edit Product Category @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.categories.edit', $category) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.categories.update', $category) }}">
            @include('pages/apps.inventory.categories._form', ['submitLabel' => 'Update Category', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
