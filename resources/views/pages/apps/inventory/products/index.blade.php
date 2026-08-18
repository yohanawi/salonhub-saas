<x-default-layout>
    @section('title')
        Products
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\Product::class)
                <a href="{{ route('inventory.products.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Add Product
                </a>
            @endcan
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" class="row g-4 align-items-end">
                    @if ($isSuperAdmin && $tenants->isNotEmpty())
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label fw-semibold">Salon</label>
                            <select name="tenant_id" class="form-select form-select-solid">
                                <option value="">All salons</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Name, SKU, barcode">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Brand</label>
                        <select name="brand_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="product_type" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($productTypes as $type)
                                <option value="{{ $type }}" @selected(request('product_type') === $type)>{{ str($type)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-6">
                        <button class="btn btn-light-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <h3 class="fw-bold mb-0">Product List</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Product</th>
                                @if ($isSuperAdmin)<th>Salon</th>@endif
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Type</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">Cost</th>
                                <th class="text-end">Selling</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($products as $product)
                                <tr>
                                    <td>
                                        <a href="{{ route('inventory.products.show', $product) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $product->name }}</a>
                                        <div class="text-muted fs-8">SKU {{ $product->sku }} @if($product->barcode)- {{ $product->barcode }}@endif</div>
                                    </td>
                                    @if ($isSuperAdmin)<td>{{ $product->tenant?->name }}</td>@endif
                                    <td>{{ $product->category?->name ?? '-' }}</td>
                                    <td>{{ $product->brand?->name ?? '-' }}</td>
                                    <td><span class="badge badge-light-info">{{ $product->product_type_label }}</span></td>
                                    <td class="text-end">{{ number_format($product->inventories->sum('quantity_on_hand')) }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $product->cost_price, 2) }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $product->selling_price, 2) }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $product->is_active ? 'success' : 'danger' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('inventory.products.show', $product) }}" class="btn btn-sm btn-icon btn-light-primary"><i class="bi bi-eye"></i></a>
                                        @can('update', $product)
                                            <a href="{{ route('inventory.products.edit', $product) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ? 10 : 9 }}" class="text-center py-12">
                                        <div class="fw-bold text-gray-900 mb-1">No products found</div>
                                        <div class="text-muted">Create products before managing branch stock.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
