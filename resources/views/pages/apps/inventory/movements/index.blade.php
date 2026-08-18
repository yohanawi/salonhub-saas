<x-default-layout>
    @section('title')
        Stock Movements
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.movements.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" class="row g-4 align-items-end">
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
                        <label class="form-label fw-semibold">Product</label>
                        <select name="product_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="type" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ str($type)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-solid">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-solid">
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <button class="btn btn-light-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <h3 class="fw-bold mb-0">Stock Movement Ledger</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Date</th>
                                <th>Product</th>
                                <th>Branch</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Before</th>
                                <th class="text-end">After</th>
                                <th>Reference</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at?->format('M d, Y h:i A') }}</td>
                                    <td>{{ $movement->product?->name }}</td>
                                    <td>{{ $movement->branch?->name }}</td>
                                    <td><span class="badge badge-light-info">{{ $movement->type_label }}</span></td>
                                    <td class="text-end fw-bold {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">{{ $movement->quantity }}</td>
                                    <td class="text-end">{{ $movement->quantity_before }}</td>
                                    <td class="text-end">{{ $movement->quantity_after }}</td>
                                    <td>{{ class_basename($movement->reference_type) }} {{ $movement->reference_id }}</td>
                                    <td>{{ $movement->creator?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-10">No stock movement history found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $movements->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
