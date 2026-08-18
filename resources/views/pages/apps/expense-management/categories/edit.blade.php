<x-default-layout>
    @section('title') Edit Expense Category @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.categories.edit', $category) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <form method="POST" action="{{ route('expense-management.categories.update', $category) }}">
            @include('pages/apps.expense-management.categories._form', ['submitLabel' => 'Update Category', 'method' => 'PUT'])
        </form>
    </div>
</x-default-layout>
