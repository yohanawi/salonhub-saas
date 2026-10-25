<x-default-layout>

    @section('title')
        Suppliers
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.suppliers.index') }}
    @endsection

    <div id="kt_app_content_container">

        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-truck text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">Suppliers</h3>
                                <span class="badge badge-light-primary px-3 py-2">{{ number_format($suppliers->total()) }} {{ Str::plural('Supplier', $suppliers->total()) }}</span>
                            </div>
                            <div class="text-muted fs-7">Companies you buy stock from. Purchase orders are raised against these suppliers.</div>
                        </div>
                    </div>

                    @can('create', \App\Models\Supplier::class)
                        <a href="{{ route('inventory.suppliers.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>Add Supplier
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <form method="GET" class="d-flex flex-wrap gap-3 w-100">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid form-control-sm w-250px"
                        placeholder="Search name, contact, phone...">
                    <select name="status" class="form-select form-select-solid form-select-sm w-150px">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                    <button class="btn btn-sm btn-light-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                </form>
            </div>

            <div class="card-body pt-4">
                @if ($suppliers->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Supplier</th>
                                    @if ($isSuperAdmin)
                                        <th class="min-w-150px">Salon</th>
                                    @endif
                                    <th class="min-w-150px">Contact</th>
                                    <th class="min-w-100px text-center">Open Orders</th>
                                    <th class="min-w-100px text-center">Status</th>
                                    <th class="min-w-150px text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @foreach ($suppliers as $supplier)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $supplier->name }}</div>
                                            @if ($supplier->tax_number)
                                                <div class="text-muted fs-8">Tax no. {{ $supplier->tax_number }}</div>
                                            @endif
                                        </td>
                                        @if ($isSuperAdmin)
                                            <td>{{ $supplier->tenant?->name }}</td>
                                        @endif
                                        <td>
                                            <div class="text-gray-800">{{ $supplier->contact_person ?: '—' }}</div>
                                            <div class="text-muted fs-8">{{ collect([$supplier->phone, $supplier->email])->filter()->implode(' · ') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $supplier->open_orders_count ? 'warning' : 'secondary' }}">{{ $supplier->open_orders_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $supplier->is_active ? 'success' : 'danger' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="text-end">
                                            @can('viewAny', \App\Models\Purchase::class)
                                                <a href="{{ route('inventory.purchases.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-light">Orders</a>
                                            @endcan
                                            @can('update', $supplier)
                                                <a href="{{ route('inventory.suppliers.edit', $supplier) }}" class="btn btn-sm btn-light-primary">Edit</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">{{ $suppliers->links() }}</div>
                @else
                    <div class="text-center py-15">
                        <i class="bi bi-truck fs-3x text-gray-300 mb-4 d-block"></i>
                        <h3 class="fw-bold text-gray-900 mb-2">No suppliers yet</h3>
                        <div class="text-muted fs-6">Add the companies you buy products from to start raising purchase orders.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-default-layout>
