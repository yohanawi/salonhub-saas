<x-default-layout>

    @section('title')
        Purchase Orders
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.purchases.index') }}
    @endsection

    <div id="kt_app_content_container">

        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-info rounded-4">
                                <i class="bi bi-cart-check text-info fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">Purchase Orders</h3>
                                <span class="badge badge-light-info px-3 py-2">{{ number_format($purchases->total()) }} {{ Str::plural('Order', $purchases->total()) }}</span>
                            </div>
                            <div class="text-muted fs-7">Order stock from suppliers and book deliveries in with a goods received note (GRN).</div>
                        </div>
                    </div>

                    @can('create', \App\Models\Purchase::class)
                        <div class="d-flex flex-wrap gap-2">
                            @if ($branches->isNotEmpty())
                                <div class="dropdown">
                                    <button class="btn btn-light-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-arrow-repeat me-1"></i>Reorder Low Stock
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach ($branches as $branch)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('inventory.purchases.create', ['reorder' => 1, 'branch_id' => $branch->id, 'tenant_id' => $isSuperAdmin ? $branch->tenant_id : null]) }}">{{ $branch->name }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <a href="{{ route('inventory.purchases.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle-fill me-2"></i>New Purchase Order
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <form method="GET" class="d-flex flex-wrap gap-3 w-100">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid form-control-sm w-200px" placeholder="PO or invoice number">
                    <select name="branch_id" class="form-select form-select-solid form-select-sm w-175px">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @if ($suppliers->isNotEmpty())
                        <select name="supplier_id" class="form-select form-select-solid form-select-sm w-175px">
                            <option value="">All suppliers</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="status" class="form-select form-select-solid form-select-sm w-175px">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-light-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                </form>
            </div>

            <div class="card-body pt-4">
                @if ($purchases->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-150px">PO Number</th>
                                    <th class="min-w-150px">Supplier</th>
                                    <th class="min-w-125px">Branch</th>
                                    <th class="min-w-100px">Date</th>
                                    <th class="min-w-75px text-center">Items</th>
                                    <th class="min-w-125px text-end">Total</th>
                                    <th class="min-w-125px text-center">Status</th>
                                    <th class="min-w-75px text-end"></th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @foreach ($purchases as $purchase)
                                    <tr>
                                        <td>
                                            <a href="{{ route('inventory.purchases.show', $purchase) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $purchase->purchase_number }}</a>
                                            @if ($purchase->invoice_number)
                                                <div class="text-muted fs-8">Inv. {{ $purchase->invoice_number }}</div>
                                            @endif
                                            @if ($isSuperAdmin)
                                                <div class="text-muted fs-8">{{ $purchase->tenant?->name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                                        <td>{{ $purchase->branch?->name }}</td>
                                        <td>
                                            {{ $purchase->purchase_date?->format('M d, Y') }}
                                            @if ($purchase->expected_date && $purchase->isReceivable())
                                                <div class="fs-8 {{ $purchase->expected_date->isPast() ? 'text-danger' : 'text-muted' }}">Due {{ $purchase->expected_date->format('M d') }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $purchase->items_count }}</td>
                                        <td class="text-end fw-semibold">{{ $purchase->tenant?->currency ?: 'LKR' }} {{ number_format((float) $purchase->total, 2) }}</td>
                                        <td class="text-center"><span class="badge badge-light-{{ $purchase->status_color }}">{{ $purchase->status_label }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('inventory.purchases.show', $purchase) }}" class="btn btn-sm btn-light-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">{{ $purchases->links() }}</div>
                @else
                    <div class="text-center py-15">
                        <i class="bi bi-cart-check fs-3x text-gray-300 mb-4 d-block"></i>
                        <h3 class="fw-bold text-gray-900 mb-2">No purchase orders</h3>
                        <div class="text-muted fs-6">Raise a purchase order when you need to restock from a supplier.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-default-layout>
