<x-default-layout>

    @section('title')
        New Supplier
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.suppliers.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('inventory.suppliers.store') }}">
            @csrf
            @include('pages/apps.inventory.suppliers._form')
        </form>
    </div>

</x-default-layout>
