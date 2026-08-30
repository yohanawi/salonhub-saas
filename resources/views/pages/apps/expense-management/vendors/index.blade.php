<x-default-layout>
    @section('title') Vendors @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.vendors.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')
        <div class="d-flex justify-content-between align-items-center mb-8">
            <form method="GET" class="w-300px">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Search vendors">
            </form>
            @can('create', \App\Models\Vendor::class)
                <a href="{{ route('expense-management.vendors.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Vendor</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Vendors</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Name</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Contact</th><th>Expenses</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($vendors as $vendor)
                                <tr>
                                    <td><div class="fw-bold text-gray-900">{{ $vendor->name }}</div><div class="text-muted fs-8">{{ $vendor->company_name }}</div></td>
                                    @if($isSuperAdmin)<td>{{ $vendor->tenant?->name }}</td>@endif
                                    <td>{{ $vendor->phone ?: $vendor->email ?: '-' }}</td>
                                    <td>{{ number_format($vendor->expenses_count) }}</td>
                                    <td><span class="badge badge-light-{{ $vendor->is_active ? 'success' : 'danger' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            @can('update', $vendor)
                                                <a href="{{ route('expense-management.vendors.edit', $vendor) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a>
                                            @endcan
                                            @can('delete', $vendor)
                                                <form method="POST" action="{{ route('expense-management.vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this vendor?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-icon btn-light-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="text-center text-muted py-10">No vendors found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $vendors->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
