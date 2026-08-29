<x-default-layout>
    @section('title') Coupons @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.coupons.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-55px me-5">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-ticket-perforated text-warning fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-warning mb-3">Coupon Desk</div>
                            <h1 class="fw-bolder text-gray-900 mb-1">Coupons</h1>
                            <div class="text-muted">Manage promotion codes, limits, and customer redemptions.</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('promotions.promotions.index') }}" class="btn btn-light">
                            <i class="bi bi-megaphone me-2"></i>Promotions
                        </a>
                        @can('create', \App\Models\PromotionCoupon::class)
                            <a href="{{ route('promotions.coupons.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Create Coupon
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold mb-1">Coupon Directory</h3>
                        <div class="text-muted fs-7">{{ number_format($coupons->total()) }} coupon records</div>
                    </div>
                </div>
                <div class="card-toolbar gap-3">
                    <form method="GET" action="{{ route('promotions.coupons.index') }}" class="d-flex align-items-center gap-3">
                        @if ($isSuperAdmin)
                            <input type="hidden" name="tenant_id" value="{{ request('tenant_id') }}">
                        @endif
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                            <input name="search" value="{{ request('search') }}" class="form-control form-control-solid ps-12 w-250px" placeholder="Search coupons">
                        </div>
                    </form>
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="bi bi-funnel me-2"></i>Filter
                    </button>
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-350px" data-kt-menu="true">
                        <form method="GET" action="{{ route('promotions.coupons.index') }}" class="px-7 py-5">
                            <div class="fs-5 fw-bold text-gray-900 mb-5">Filter Coupons</div>
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            @if ($isSuperAdmin)
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Tenant</label>
                                    <select name="tenant_id" class="form-select form-select-solid">
                                        <option value="">All tenants</option>
                                        @foreach ($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-7">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select form-select-solid">
                                    <option value="">All statuses</option>
                                    @foreach (\App\Models\PromotionCoupon::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('promotions.coupons.index') }}" class="btn btn-light btn-sm">Reset</a>
                                <button class="btn btn-primary btn-sm">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                @if (request()->filled('search') || request()->filled('status') || request()->filled('tenant_id'))
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-6">
                        <i class="bi bi-sliders text-primary fs-2 me-4"></i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold text-gray-700">Filtered coupon results are currently shown.</div>
                            <a href="{{ route('promotions.coupons.index') }}" class="btn btn-sm btn-light-primary">Clear</a>
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Code</th>
                                <th>Promotion</th>
                                <th>Period</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($coupons as $coupon)
                                @php
                                    $statusColor = $coupon->status === \App\Models\PromotionCoupon::STATUS_ACTIVE ? 'success' : ($coupon->status === \App\Models\PromotionCoupon::STATUS_INACTIVE ? 'warning' : 'secondary');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-{{ $statusColor }}">
                                                    <i class="bi bi-ticket-detailed text-{{ $statusColor }} fs-3"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">{{ $coupon->code }}</div>
                                                <div class="text-muted fs-8">Coupon Code</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-gray-800">{{ $coupon->promotion?->name ?? '-' }}</td>
                                    <td>
                                        <div class="text-gray-800">{{ $coupon->starts_at?->format('M d, Y') ?? 'Now' }}</div>
                                        <div class="text-muted fs-8">to {{ $coupon->expires_at?->format('M d, Y') ?? 'No end' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-gray-900">{{ number_format($coupon->usage_count) }}{{ $coupon->usage_limit ? ' / ' . number_format($coupon->usage_limit) : '' }}</div>
                                        <div class="text-muted fs-8">redemptions</div>
                                    </td>
                                    <td><span class="badge badge-light-{{ $statusColor }}">{{ $coupon->status_label }}</span></td>
                                    <td class="text-end">
                                        @can('update', $coupon)
                                            <a href="{{ route('promotions.coupons.edit', $coupon) }}" class="btn btn-icon btn-sm btn-light-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-12">
                                        <div class="symbol symbol-60px mx-auto mb-4">
                                            <div class="symbol-label bg-light">
                                                <i class="bi bi-ticket-perforated text-muted fs-1"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-gray-800">No coupons configured.</div>
                                        <div class="text-muted fs-7">Create a coupon for any promotion that needs a code.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
