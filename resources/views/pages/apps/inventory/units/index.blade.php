<x-default-layout>
    @section('title') Product Units @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.units.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\Unit::class)
                <a href="{{ route('inventory.units.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Unit</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Product Units</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Name</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Symbol</th><th>Type</th><th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($units as $unit)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $unit->name }}</td>
                                    @if($isSuperAdmin)<td>{{ $unit->tenant?->name }}</td>@endif
                                    <td>{{ $unit->symbol }}</td>
                                    <td>{{ $unit->type_label }}</td>
                                    <td>{{ number_format($unit->products_count) }}</td>
                                    <td><span class="badge badge-light-{{ $unit->is_active ? 'success' : 'danger' }}">{{ $unit->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end"><a href="{{ route('inventory.units.edit', $unit) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-10">No product units found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $units->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
