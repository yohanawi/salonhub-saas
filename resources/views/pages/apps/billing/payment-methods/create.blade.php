<x-default-layout>
    @section('title')
        Add Payment Method
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payment-methods.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('billing.payment-methods.store') }}">
            @csrf
            @include('pages.apps.billing.payment-methods._form')
        </form>
    </div>
</x-default-layout>
