<x-default-layout>
    @section('title')
        Edit Payment Method
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payment-methods.edit', $paymentMethod) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('billing.payment-methods.update', $paymentMethod) }}">
            @csrf
            @method('PUT')
            @include('pages.apps.billing.payment-methods._form')
        </form>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
