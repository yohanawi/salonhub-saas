<x-default-layout>
    @section('title') Edit Expense @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.expenses.edit', $expense) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <form method="POST" action="{{ route('expense-management.expenses.update', $expense) }}">
            @include('pages/apps.expense-management.expenses._form', ['submitLabel' => 'Update Expense', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
