<x-default-layout>
    @section('title') Add Vendor @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.vendors.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <form method="POST" action="{{ route('expense-management.vendors.store') }}">
            @include('pages/apps.expense-management.vendors._form', ['submitLabel' => 'Create Vendor'])
        </form>
    </div>
</x-default-layout>
