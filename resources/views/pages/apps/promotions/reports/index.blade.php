<x-default-layout>
    @section('title') Promotion Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.reports.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-55px me-5">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-bar-chart-line text-success fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-success mb-3">Performance Center</div>
                            <h1 class="fw-bolder text-gray-900 mb-1">Promotion Reports</h1>
                            <div class="text-muted">Compare redemptions and discount value by promotion.</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="bi bi-funnel me-2"></i>Filter Report
                    </button>
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-350px" data-kt-menu="true">
                        <form method="GET" action="{{ route('promotions.reports.index') }}" class="px-7 py-5">
                            <div class="fs-5 fw-bold text-gray-900 mb-5">Report Filters</div>
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
                            <div class="mb-5">
                                <label class="form-label fw-semibold">From</label>
                                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-solid">
                            </div>
                            <div class="mb-7">
                                <label class="form-label fw-semibold">To</label>
                                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-solid">
                            </div>
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('promotions.reports.index') }}" class="btn btn-light btn-sm">Reset</a>
                                <button class="btn btn-primary btn-sm">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (request()->filled('from') || request()->filled('to') || request()->filled('tenant_id'))
            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-8">
                <i class="bi bi-calendar-check text-primary fs-2 me-4"></i>
                <div class="d-flex flex-stack flex-grow-1">
                    <div class="fw-semibold text-gray-700">Filtered report results are currently shown.</div>
                    <a href="{{ route('promotions.reports.index') }}" class="btn btn-sm btn-light-primary">Clear</a>
                </div>
            </div>
        @endif

        <div class="row g-6 mb-8">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-success"><i class="bi bi-broadcast text-success fs-2"></i></div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Active Promotions</div>
                            <div class="fs-2hx fw-bolder text-gray-900">{{ number_format($activePromotions) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-info"><i class="bi bi-arrow-repeat text-info fs-2"></i></div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Redemptions</div>
                            <div class="fs-2hx fw-bolder text-gray-900">{{ number_format($totalRedemptions) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-6">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-danger"><i class="bi bi-cash-coin text-danger fs-2"></i></div>
                        </div>
                        <div>
                            <div class="text-gray-500 fw-semibold fs-7 text-uppercase">Discount Given</div>
                            <div class="fs-3 fw-bolder text-gray-900">LKR {{ number_format((float) $discountGiven, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold mb-1">Promotion Performance</h3>
                        <div class="text-muted fs-7">Top discount impact across the selected period</div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Promotion</th>
                                <th>Redemptions</th>
                                <th class="text-end">Discount Given</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byPromotion as $row)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-graph-up-arrow text-primary fs-3"></i>
                                                </div>
                                            </div>
                                            <div class="fw-bold text-gray-900">{{ $row->promotion?->name ?? 'Deleted Promotion' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-success fw-bold">{{ number_format($row->redemptions) }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-gray-900">LKR {{ number_format((float) $row->discount_given, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-12">
                                        <div class="symbol symbol-60px mx-auto mb-4">
                                            <div class="symbol-label bg-light">
                                                <i class="bi bi-bar-chart text-muted fs-1"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-gray-800">No promotion report data yet.</div>
                                        <div class="text-muted fs-7">Usage data will appear here after discounts are redeemed.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
