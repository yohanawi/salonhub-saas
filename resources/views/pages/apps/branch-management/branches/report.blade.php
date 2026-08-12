<x-default-layout>

    @section('title')
        Branch Reports
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.reports.show', $branch) }}
    @endsection

    <div id="kt_app_content_container">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5 mb-7">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">
                    {{ $branch->name }} Reports
                </h1>
                <div class="text-muted fw-semibold">
                    {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}
                </div>
            </div>

            <form method="GET" action="{{ route('branches.reports.show', $branch) }}" class="d-flex flex-wrap gap-3">
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control w-auto">
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control w-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter me-1"></i>
                    Apply
                </button>
            </form>
        </div>

        <div class="row g-5 mb-7">
            @foreach ([
                ['Appointments', $metrics['appointments'], 'bi-calendar-check', 'primary'],
                ['Upcoming', $metrics['upcoming_appointments'], 'bi-clock-history', 'info'],
                ['Sales Total', $branch->currency . ' ' . number_format($metrics['sales_total'], 2), 'bi-cash-stack', 'success'],
                ['Payments', $branch->currency . ' ' . number_format($metrics['payments_total'], 2), 'bi-credit-card', 'success'],
                ['Purchases', $branch->currency . ' ' . number_format($metrics['purchases_total'], 2), 'bi-bag-check', 'warning'],
                ['Expenses', $branch->currency . ' ' . number_format($metrics['expenses_total'], 2), 'bi-receipt-cutoff', 'danger'],
                ['Inventory Items', $metrics['inventory_items'], 'bi-box-seam', 'primary'],
                ['Stock Quantity', $metrics['inventory_quantity'], 'bi-boxes', 'info'],
                ['Staff', $metrics['assigned_staff'], 'bi-people', 'primary'],
                ['Services', $metrics['available_services'], 'bi-scissors', 'warning'],
            ] as [$label, $value, $icon, $color])
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <div class="symbol-label bg-light-{{ $color }}">
                                    <i class="bi {{ $icon }} fs-2 text-{{ $color }}"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-7">{{ $label }}</div>
                                <div class="fw-bold fs-3 text-gray-900">{{ $value }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-7">
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <h2 class="fw-bold mb-0">Recent Appointments</h2>
                    </div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Staff</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentAppointments as $appointment)
                                        <tr>
                                            <td class="fw-semibold">{{ $appointment->starts_at->format('d M, H:i') }}</td>
                                            <td>{{ trim(($appointment->customer?->first_name ?? '') . ' ' . ($appointment->customer?->last_name ?? '')) ?: '-' }}</td>
                                            <td>{{ trim(($appointment->staff?->first_name ?? '') . ' ' . ($appointment->staff?->last_name ?? '')) ?: '-' }}</td>
                                            <td><span class="badge badge-light">{{ str($appointment->status)->headline() }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-8">No appointments in this period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <h2 class="fw-bold mb-0">Recent Sales</h2>
                    </div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentSales as $sale)
                                        <tr>
                                            <td class="fw-semibold">{{ $sale->invoice_number }}</td>
                                            <td>{{ trim(($sale->customer?->first_name ?? '') . ' ' . ($sale->customer?->last_name ?? '')) ?: '-' }}</td>
                                            <td>{{ $branch->currency }} {{ number_format((float) $sale->total, 2) }}</td>
                                            <td><span class="badge badge-light">{{ str($sale->status)->headline() }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-8">No sales in this period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
