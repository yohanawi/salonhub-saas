<x-default-layout>
    @section('title')
        Customer Packages
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.customer-packages.index') }}
    @endsection

    @php
        $statusColors = ['active' => 'success', 'completed' => 'secondary', 'cancelled' => 'danger'];
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 d-flex flex-column flex-md-row justify-content-between gap-6">
                <div>
                    <h1 class="fw-bolder text-gray-900 mb-2">Customer Packages</h1>
                    <div class="text-muted">Packages customers have bought, and how many sessions they have left.</div>
                </div>
                <div class="d-flex gap-3 align-self-start">
                    <a href="{{ route('billing.service-packages.index') }}" class="btn btn-light">Package Catalogue</a>
                    @if ($canSell)
                        <a href="{{ route('billing.customer-packages.create') }}" class="btn btn-primary">
                            <i class="bi bi-bag-plus me-2"></i>Sell Package
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <form method="GET" class="d-flex flex-wrap gap-3 w-100">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid w-250px" placeholder="Customer, phone or package">
                    <select name="status" class="form-select form-select-solid w-175px">
                        <option value="">All statuses</option>
                        @foreach (['active', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-light-primary">Filter</button>
                </form>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-4">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Number</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Remaining</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packages as $package)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $package->package_number }}</td>
                                    <td>
                                        <div>{{ $package->customer?->full_name }}</div>
                                        <div class="text-muted fs-8">{{ $package->customer?->phone }}</div>
                                    </td>
                                    <td>{{ $package->name }}</td>
                                    <td class="fs-7">
                                        @foreach ($package->items as $item)
                                            <div>{{ $item->remainingQuantity() }} / {{ $item->total_quantity }} {{ $item->service_name }}</div>
                                        @endforeach
                                    </td>
                                    <td>{{ $package->expires_at?->format('M d, Y') ?? 'No expiry' }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $package->status_label === 'Expired' ? 'warning' : ($statusColors[$package->status] ?? 'secondary') }}">{{ $package->status_label }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('billing.customer-packages.show', $package) }}" class="btn btn-sm btn-light-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-10">No packages have been sold yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $packages->links() }}
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
