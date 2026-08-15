<x-default-layout>
    @section('title')
        Add Customer
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('customer-management.customers.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('customer-management.customers.store') }}">
            @csrf
            @include('pages/apps.customer-management.customers._form')
        </form>
    </div>
</x-default-layout>
