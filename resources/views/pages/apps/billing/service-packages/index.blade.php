<x-default-layout>
    @section('title')
        Service Packages
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.service-packages.index') }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 d-flex flex-column flex-md-row justify-content-between gap-6">
                <div>
                    <h1 class="fw-bolder text-gray-900 mb-2">Service Packages</h1>
                    <div class="text-muted">Prepaid bundles such as "5 Facials for LKR 20,000". Credits are used up at checkout.</div>
                </div>
                <div class="d-flex gap-3 align-self-start">
                    <a href="{{ route('billing.customer-packages.index') }}" class="btn btn-light">Sold Packages</a>
                    @if ($canSell)
                        <a href="{{ route('billing.customer-packages.create') }}" class="btn btn-light-primary">Sell Package</a>
                    @endif
                    @if ($canManage)
                        <a href="{{ route('billing.service-packages.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>New Package
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-8">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-4">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Package</th>
                                <th>Includes</th>
                                <th>Valid For</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Sold</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packages as $package)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-gray-900">{{ $package->name }}</div>
                                        @if ($package->description)
                                            <div class="text-muted fs-8">{{ Str::limit($package->description, 80) }}</div>
                                        @endif
                                    </td>
                                    <td class="fs-7">{{ $package->summary }}</td>
                                    <td>{{ $package->validity_days ? $package->validity_days . ' days' : 'No expiry' }}</td>
                                    <td class="text-end fw-bold">LKR {{ number_format((float) $package->price, 2) }}</td>
                                    <td class="text-end">{{ $package->customer_packages_count }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $package->is_active ? 'success' : 'secondary' }}">{{ $package->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if ($canManage)
                                            <a href="{{ route('billing.service-packages.edit', $package) }}" class="btn btn-sm btn-light-primary">Edit</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-10">No packages yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
