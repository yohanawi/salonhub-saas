<x-default-layout>
    @section('title')
        {{ $customer->first_name }} Edit Customer
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('customer-management.customers.edit', $customer) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('customer-management.customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('pages/apps.customer-management.customers._form')
        </form>
    </div>

    @include('pages.apps.customer-management.customers._sweet-alerts')
</x-default-layout>
