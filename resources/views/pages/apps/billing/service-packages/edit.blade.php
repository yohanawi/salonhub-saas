<x-default-layout>
    @section('title')
        Edit {{ $package->name }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.service-packages.edit', $package) }}
    @endsection

    <div id="kt_app_content_container">
        <div class="alert alert-info border-0 mb-8 fs-7">
            Customers who already bought this package keep the sessions they paid for. Changes only apply to new sales.
        </div>
        <form method="POST" action="{{ route('billing.service-packages.update', $package) }}">
            @csrf
            @method('PUT')
            @include('pages.apps.billing.service-packages._form')
        </form>
    </div>
</x-default-layout>
