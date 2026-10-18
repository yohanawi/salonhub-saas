<x-default-layout>
    @section('title')
        New Service Package
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.service-packages.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('billing.service-packages.store') }}">
            @csrf
            @include('pages.apps.billing.service-packages._form')
        </form>
    </div>
</x-default-layout>
