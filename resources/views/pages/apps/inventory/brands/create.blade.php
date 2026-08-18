<x-default-layout>
    @section('title') Add Product Brand @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.brands.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.brands.store') }}">
            @include('pages/apps.inventory.brands._form', ['submitLabel' => 'Create Brand'])
        </form>
    </div>
</x-default-layout>
