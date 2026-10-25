<x-default-layout>

    @section('title')
        New Purchase Order
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.purchases.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('inventory.purchases.store') }}">
            @csrf
            @include('pages/apps.inventory.purchases._form')
        </form>
    </div>

</x-default-layout>
