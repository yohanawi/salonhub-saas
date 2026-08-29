<x-default-layout>
    @section('title') Promotion Usage @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.usages.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-55px me-5">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-clock-history text-info fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-info mb-3">Redemption Log</div>
                            <h1 class="fw-bolder text-gray-900 mb-1">Usage History</h1>
                            <div class="text-muted">Review promotion usage across invoices, branches, and customers.</div>
                        </div>
                    </div>
                    <a href="{{ route('promotions.reports.index') }}" class="btn btn-light-primary">
                        <i class="bi bi-bar-chart-line me-2"></i>Reports
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold mb-1">Usage History</h3>
                        <div class="text-muted fs-7">{{ number_format($usages->total()) }} usage records</div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="bi bi-funnel me-2"></i>Filter
                    </button>
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-350px" data-kt-menu="true">
                        <form method="GET" action="{{ route('promotions.usages.index') }}" class="px-7 py-5">
                            <div class="fs-5 fw-bold text-gray-900 mb-5">Filter Usage</div>
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
                                    @foreach ([\App\Models\PromotionUsage::STATUS_USED, \App\Models\PromotionUsage::STATUS_REVERSED] as $status)
                                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('promotions.usages.index') }}" class="btn btn-light btn-sm">Reset</a>
                                <button class="btn btn-primary btn-sm">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                @if (request()->filled('status') || request()->filled('tenant_id'))
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-6">
                        <i class="bi bi-sliders text-primary fs-2 me-4"></i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold text-gray-700">Filtered usage records are currently shown.</div>
                            <a href="{{ route('promotions.usages.index') }}" class="btn btn-sm btn-light-primary">Clear</a>
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Promotion</th>
                                <th>Coupon</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Invoice</th>
                                <th class="text-end">Discount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($usages as $usage)
                                @php
                                    $statusColor = $usage->status === \App\Models\PromotionUsage::STATUS_USED ? 'success' : 'warning';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-{{ $statusColor }}">
                                                    <i class="bi bi-receipt-cutoff text-{{ $statusColor }} fs-3"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">{{ $usage->promotion?->name ?? 'Promotion removed' }}</div>
                                                <div class="text-muted fs-8">{{ $usage->used_at?->format('M d, Y h:i A') ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-light">{{ $usage->coupon?->code ?? '-' }}</span></td>
                                    <td class="text-gray-700">{{ $usage->customer?->full_name ?? '-' }}</td>
                                    <td class="text-gray-700">{{ $usage->branch?->name ?? '-' }}</td>
                                    <td><span class="badge badge-light-primary">{{ $usage->invoice?->invoice_number ?? '-' }}</span></td>
                                    <td class="text-end fw-bold text-gray-900">LKR {{ number_format((float) $usage->discount_amount, 2) }}</td>
                                    <td><span class="badge badge-light-{{ $statusColor }}">{{ str($usage->status)->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12">
                                        <div class="symbol symbol-60px mx-auto mb-4">
                                            <div class="symbol-label bg-light">
                                                <i class="bi bi-clock-history text-muted fs-1"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-gray-800">No promotion usage recorded.</div>
                                        <div class="text-muted fs-7">Redemptions will appear here after checkout.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $usages->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
