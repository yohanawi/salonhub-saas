<x-default-layout>
    @section('title') Edit Vendor @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.vendors.edit', $vendor) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <form method="POST" action="{{ route('expense-management.vendors.update', $vendor) }}">
            @include('pages/apps.expense-management.vendors._form', ['submitLabel' => 'Update Vendor', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
