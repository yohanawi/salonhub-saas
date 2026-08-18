<x-default-layout>
    @section('title')
        Add Product
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.products.store') }}">
            @include('pages/apps.inventory.products._form', ['submitLabel' => 'Create Product'])
        </form>
    </div>
</x-default-layout>
