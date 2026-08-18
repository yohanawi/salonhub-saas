<x-default-layout>
    @section('title') Product Brands @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.brands.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\ProductBrand::class)
                <a href="{{ route('inventory.brands.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Brand</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Product Brands</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Name</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($brands as $brand)
                                <tr>
                                    <td><div class="fw-bold text-gray-900">{{ $brand->name }}</div><div class="text-muted fs-8">{{ $brand->description }}</div></td>
                                    @if($isSuperAdmin)<td>{{ $brand->tenant?->name }}</td>@endif
                                    <td>{{ number_format($brand->products_count) }}</td>
                                    <td><span class="badge badge-light-{{ $brand->is_active ? 'success' : 'danger' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end"><a href="{{ route('inventory.brands.edit', $brand) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 5 : 4 }}" class="text-center text-muted py-10">No product brands found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $brands->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
