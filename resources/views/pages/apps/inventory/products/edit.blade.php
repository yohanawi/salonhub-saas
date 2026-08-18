<x-default-layout>
    @section('title')
        Edit Product
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.edit', $product) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.products.update', $product) }}">
            @include('pages/apps.inventory.products._form', ['submitLabel' => 'Update Product', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
