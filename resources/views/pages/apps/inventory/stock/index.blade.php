<x-default-layout>
    @section('title')
        Current Stock
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.stock.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" class="row g-4 align-items-end">
                    @if ($isSuperAdmin && $tenants->isNotEmpty())
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label fw-semibold">Salon</label>
                            <select name="tenant_id" class="form-select form-select-solid">
                                <option value="">All</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Product, SKU, barcode">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold">Stock Status</label>
                        <select name="stock_status" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach (['in_stock', 'low_stock', 'out_of_stock'] as $status)
                                <option value="{{ $status }}" @selected(request('stock_status') === $status)>{{ str($status)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <button class="btn btn-light-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <h3 class="fw-bold mb-0">Current Stock</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Product</th>
                                <th>Branch</th>
                                <th>Category</th>
                                <th class="text-end">On Hand</th>
                                <th class="text-end">Reserved</th>
                                <th class="text-end">Available</th>
                                <th class="text-end">Reorder</th>
                                <th class="text-end">Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($stocks as $stock)
                                <tr>
                                    <td>
                                        <a href="{{ route('inventory.products.show', $stock->product) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $stock->product?->name }}</a>
                                        <div class="text-muted fs-8">SKU {{ $stock->product?->sku }}</div>
                                    </td>
                                    <td>{{ $stock->branch?->name }}</td>
                                    <td>{{ $stock->product?->category?->name ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($stock->quantity_on_hand) }}</td>
                                    <td class="text-end">{{ number_format($stock->quantity_reserved) }}</td>
                                    <td class="text-end">{{ number_format($stock->available_quantity) }}</td>
                                    <td class="text-end">{{ number_format($stock->product?->reorder_level ?? 0) }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $stock->average_cost * (int) $stock->quantity_on_hand, 2) }}</td>
                                    <td><span class="badge badge-light-{{ $stock->stock_status === 'in_stock' ? 'success' : ($stock->stock_status === 'low_stock' ? 'warning' : 'danger') }}">{{ $stock->stock_status_label }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-10">No branch stock records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $stocks->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
