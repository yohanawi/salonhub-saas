<x-default-layout>

    @section('title')
        Edit {{ $purchase->purchase_number }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.purchases.edit', $purchase) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('inventory.purchases.update', $purchase) }}">
            @csrf
            @method('PUT')
            @include('pages/apps.inventory.purchases._form')
        </form>
    </div>

</x-default-layout>
