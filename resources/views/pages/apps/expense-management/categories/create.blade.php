<x-default-layout>
    @section('title') Add Expense Category @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.categories.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <form method="POST" action="{{ route('expense-management.categories.store') }}">
            @include('pages/apps.expense-management.categories._form', ['submitLabel' => 'Create Category'])
        </form>
    </div>
</x-default-layout>
