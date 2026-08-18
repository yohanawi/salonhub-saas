<x-default-layout>
    @section('title') Add Product Unit @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.units.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.units.store') }}">
            @include('pages/apps.inventory.units._form', ['submitLabel' => 'Create Unit'])
        </form>
    </div>
</x-default-layout>
