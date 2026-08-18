<x-default-layout>
    @section('title')
        Inventory Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.dashboard') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        @if ($isSuperAdmin && $tenants->isNotEmpty())
            <div class="card border-0 shadow-sm mb-8">
                <div class="card-body py-5">
                    <form method="GET" class="row g-4 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Salon</label>
                            <select name="tenant_id" class="form-select form-select-solid">
                                <option value="">All salons</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-light-primary w-100">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="row g-6 mb-8">
            @foreach ([
                ['Total Products', $totalProducts, 'primary'],
                ['Active Products', $activeProducts, 'success'],
                ['Stock Value', 'LKR ' . number_format($inventoryValue, 2), 'info'],
                ['Low Stock', $lowStockCount, 'warning'],
                ['Out Of Stock', $outOfStockCount, 'danger'],
            ] as [$label, $value, $color])
                <div class="col-xl col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7 mb-2">{{ $label }}</div>
                            <div class="fw-bolder fs-2 text-{{ $color }}">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <h3 class="fw-bold mb-0">Low Stock Products</h3>
                    </div>
                    <div class="card-body pt-3">
                        @forelse ($lowStockItems as $stock)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $stock->product?->name }}</div>
                                    <div class="text-muted fs-8">{{ $stock->branch?->name }} - SKU {{ $stock->product?->sku }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge badge-light-warning">{{ $stock->quantity_on_hand }} left</span>
                                    <div class="text-muted fs-8">Reorder {{ $stock->product?->reorder_level }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-6">No low stock products found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <h3 class="fw-bold mb-0">Recent Stock Movements</h3>
                    </div>
                    <div class="card-body pt-3">
                        @forelse ($recentMovements as $movement)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $movement->product?->name }}</div>
                                    <div class="text-muted fs-8">{{ $movement->branch?->name }} - {{ $movement->type_label }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">{{ $movement->quantity }}</span>
                                    <div class="text-muted fs-8">{{ $movement->created_at?->format('M d, h:i A') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-6">No stock movement history yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
