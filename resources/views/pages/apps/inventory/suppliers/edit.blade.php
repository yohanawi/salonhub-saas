<x-default-layout>

    @section('title')
        Edit Supplier
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.suppliers.edit', $supplier) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('inventory.suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')
            @include('pages/apps.inventory.suppliers._form')
        </form>
    </div>

</x-default-layout>
