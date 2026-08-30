<x-default-layout>
    @section('title') Expense Categories @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.categories.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\ExpenseCategory::class)
                <a href="{{ route('expense-management.categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Category</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Expense Categories</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Name</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Code</th><th>Parent</th><th>Expenses</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $category->name }}</td>
                                    @if($isSuperAdmin)<td>{{ $category->tenant?->name }}</td>@endif
                                    <td>{{ $category->code }}</td>
                                    <td>{{ $category->parent?->name ?? '-' }}</td>
                                    <td>{{ number_format($category->expenses_count) }}</td>
                                    <td><span class="badge badge-light-{{ $category->is_active ? 'success' : 'danger' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            @can('update', $category)
                                                <a href="{{ route('expense-management.categories.edit', $category) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a>
                                            @endcan
                                            @can('delete', $category)
                                                <form method="POST" action="{{ route('expense-management.categories.destroy', $category) }}" onsubmit="return confirm('Delete this expense category?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-icon btn-light-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-10">No expense categories found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $categories->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
