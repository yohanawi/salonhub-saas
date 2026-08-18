<x-default-layout>
    @section('title') Edit Product Brand @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.brands.edit', $brand) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.brands.update', $brand) }}">
            @include('pages/apps.inventory.brands._form', ['submitLabel' => 'Update Brand', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
