<x-default-layout>
    @section('title') Add Expense @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.expenses.create') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <form method="POST" action="{{ route('expense-management.expenses.store') }}" enctype="multipart/form-data">
            @include('pages/apps.expense-management.expenses._form', ['submitLabel' => 'Create Expense'])
        </form>
    </div>
</x-default-layout>
