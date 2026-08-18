<x-default-layout>
    @section('title')
        Product
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.show', $product) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h1 class="fw-bolder text-gray-900 mb-0">{{ $product->name }}</h1>
                            <span class="badge badge-light-info">{{ $product->product_type_label }}</span>
                            <span class="badge badge-light-{{ $product->is_active ? 'success' : 'danger' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="text-muted mt-2">SKU {{ $product->sku }} @if($product->barcode)- Barcode {{ $product->barcode }}@endif</div>
                    </div>
                    <div class="d-flex gap-3 align-self-start">
                        @can('update', $product)
                            <a href="{{ route('inventory.products.edit', $product) }}" class="btn btn-light-primary"><i class="bi bi-pencil-square me-2"></i>Edit</a>
                        @endcan
                        <a href="{{ route('inventory.products.index') }}" class="btn btn-light">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Overview</h3></div>
                    <div class="card-body">
                        @foreach ([
                            'Category' => $product->category?->name ?? '-',
                            'Brand' => $product->brand?->name ?? '-',
                            'Unit' => $product->unit ? $product->unit->name . ' (' . $product->unit->symbol . ')' : '-',
                            'Cost' => 'LKR ' . number_format((float) $product->cost_price, 2),
                            'Selling' => 'LKR ' . number_format((float) $product->selling_price, 2),
                            'Margin' => 'LKR ' . number_format($product->margin_amount, 2) . ' (' . $product->margin_percentage . '%)',
                            'Reorder Level' => number_format($product->reorder_level),
                        ] as $label => $value)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <span class="text-muted">{{ $label }}</span>
                                <span class="fw-semibold text-gray-900 text-end">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Stock by Branch</h3></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Branch</th>
                                        <th class="text-end">On Hand</th>
                                        <th class="text-end">Reserved</th>
                                        <th class="text-end">Available</th>
                                        <th class="text-end">Average Cost</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($product->inventories as $stock)
                                        <tr>
                                            <td class="fw-semibold">{{ $stock->branch?->name }}</td>
                                            <td class="text-end">{{ number_format($stock->quantity_on_hand) }}</td>
                                            <td class="text-end">{{ number_format($stock->quantity_reserved) }}</td>
                                            <td class="text-end">{{ number_format($stock->available_quantity) }}</td>
                                            <td class="text-end">LKR {{ number_format((float) $stock->average_cost, 2) }}</td>
                                            <td><span class="badge badge-light-{{ $stock->stock_status === 'in_stock' ? 'success' : ($stock->stock_status === 'low_stock' ? 'warning' : 'danger') }}">{{ $stock->stock_status_label }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-8">No branch stock recorded yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Recent Movements</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($product->stockMovements->sortByDesc('created_at')->take(10) as $movement)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $movement->type_label }}</div>
                                    <div class="text-muted fs-8">{{ $movement->branch?->name }} - {{ $movement->created_at?->format('M d, Y h:i A') }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="{{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }} fw-bold">{{ $movement->quantity }}</span>
                                    <div class="text-muted fs-8">{{ $movement->quantity_before }} to {{ $movement->quantity_after }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-6">No stock movements yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
