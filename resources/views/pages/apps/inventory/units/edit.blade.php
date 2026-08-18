<x-default-layout>
    @section('title') Edit Product Unit @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.units.edit', $unit) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <form method="POST" action="{{ route('inventory.units.update', $unit) }}">
            @include('pages/apps.inventory.units._form', ['submitLabel' => 'Update Unit', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
