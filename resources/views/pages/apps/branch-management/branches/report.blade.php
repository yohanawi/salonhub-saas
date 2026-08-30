<x-default-layout>

    @section('title')
        Branch Reports
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.reports.show', $branch) }}
    @endsection

    <div id="kt_app_content_container">

        {{-- ========================================================= --}}
        {{-- REPORT HEADER --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-5">

            <div class="card-body p-4 p-lg-6">

                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-8">

                    {{-- Left --}}
                    <div class="d-flex align-items-start gap-5">

                        <div class="symbol symbol-50px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-bar-chart-line text-primary fs-1"></i>
                            </div>
                        </div>

                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $branch->name }} Reports
                                </h3>

                                <span class="badge badge-light-primary px-3 py-2">
                                    Branch Analytics
                                </span>

                            </div>

                            <div class="d-flex flex-wrap gap-4 text-muted fs-7">

                                <span>
                                    <i class="bi bi-calendar-range me-1"></i>
                                    {{ $startDate->format('d M Y') }}
                                    -
                                    {{ $endDate->format('d M Y') }}
                                </span>

                                <span>
                                    <i class="bi bi-geo-alt me-1"></i>
                                    {{ $branch->name }}
                                </span>

                                <span>
                                    <i class="bi bi-cash-stack me-1"></i>
                                    {{ $branch->currency }}
                                </span>

                            </div>

                        </div>

                    </div>


                    {{-- Date Filter --}}
                    <form method="GET" action="{{ route('branches.reports.show', $branch) }}"
                        class="d-flex flex-column flex-md-row align-items-md-end gap-3">

                        <div>
                            <label class="form-label fw-semibold text-gray-700 fs-8">
                                From
                            </label>

                            <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
                                class="form-control">
                        </div>


                        <div>
                            <label class="form-label fw-semibold text-gray-700 fs-8">
                                To
                            </label>

                            <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
                                class="form-control">
                        </div>


                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel-fill me-2"></i>
                            Apply Range
                        </button>

                    </form>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- PRIMARY METRICS --}}
        {{-- ========================================================= --}}
        <div class="row g-5 mb-8">

            @php
                $reportMetrics = [
                    [
                        'label' => 'Appointments',
                        'value' => $metrics['appointments'],
                        'icon' => 'bi-calendar-check',
                        'color' => 'primary',
                        'caption' => 'Total bookings',
                    ],
                    [
                        'label' => 'Upcoming',
                        'value' => $metrics['upcoming_appointments'],
                        'icon' => 'bi-clock-history',
                        'color' => 'info',
                        'caption' => 'Future bookings',
                    ],
                    [
                        'label' => 'Sales Total',
                        'value' => $branch->currency . ' ' . number_format($metrics['sales_total'], 2),
                        'icon' => 'bi-cash-stack',
                        'color' => 'success',
                        'caption' => 'Gross sales',
                    ],
                    [
                        'label' => 'Payments',
                        'value' => $branch->currency . ' ' . number_format($metrics['payments_total'], 2),
                        'icon' => 'bi-credit-card',
                        'color' => 'success',
                        'caption' => 'Collected amount',
                    ],
                    [
                        'label' => 'Purchases',
                        'value' => $branch->currency . ' ' . number_format($metrics['purchases_total'], 2),
                        'icon' => 'bi-bag-check',
                        'color' => 'warning',
                        'caption' => 'Stock purchases',
                    ],
                    [
                        'label' => 'Expenses',
                        'value' => $branch->currency . ' ' . number_format($metrics['expenses_total'], 2),
                        'icon' => 'bi-receipt-cutoff',
                        'color' => 'danger',
                        'caption' => 'Operating expenses',
                    ],
                    [
                        'label' => 'Inventory Items',
                        'value' => $metrics['inventory_items'],
                        'icon' => 'bi-box-seam',
                        'color' => 'primary',
                        'caption' => 'Product SKUs',
                    ],
                    [
                        'label' => 'Stock Quantity',
                        'value' => $metrics['inventory_quantity'],
                        'icon' => 'bi-boxes',
                        'color' => 'info',
                        'caption' => 'Units in stock',
                    ],
                    [
                        'label' => 'Staff',
                        'value' => $metrics['assigned_staff'],
                        'icon' => 'bi-people',
                        'color' => 'primary',
                        'caption' => 'Assigned team',
                    ],
                    [
                        'label' => 'Services',
                        'value' => $metrics['available_services'],
                        'icon' => 'bi-scissors',
                        'color' => 'warning',
                        'caption' => 'Available services',
                    ],
                ];
            @endphp

            @foreach ($reportMetrics as $metric)
                <div class="col-md-6 col-xl-2 mx-auto">
                    <div class="card border-0 mx-auto shadow-sm">
                        <div class="card-body p-6 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="symbol symbol-45px">
                                    <div class="symbol-label bg-light-{{ $metric['color'] }}">
                                        <i class="bi {{ $metric['icon'] }} fs-3 text-{{ $metric['color'] }}"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted fs-8 fw-semibold text-uppercase">
                                    {{ $metric['label'] }}
                                </div>
                                <div class="fw-bolder fs-4 text-gray-900">
                                    {{ $metric['value'] }}
                                </div>
                                <div class="text-muted fs-9">
                                    {{ $metric['caption'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

        {{-- ========================================================= --}}
        {{-- RECENT ACTIVITY --}}
        {{-- ========================================================= --}}
        <div class="row g-8">

            {{-- ===================================================== --}}
            {{-- RECENT APPOINTMENTS --}}
            {{-- ===================================================== --}}
            <div class="col-xl-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-calendar2-check text-primary fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Recent Appointments
                                </h2>

                                <div class="text-muted fs-8">
                                    Latest bookings within the selected period.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @if ($recentAppointments->isNotEmpty())

                            <div class="table-responsive">

                                <table class="table align-middle table-row-dashed gy-5">

                                    <thead>

                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th class="min-w-130px">Date</th>
                                            <th class="min-w-160px">Customer</th>
                                            <th class="min-w-160px">Staff</th>
                                            <th class="min-w-110px">Status</th>
                                        </tr>

                                    </thead>


                                    <tbody class="fw-semibold text-gray-700">

                                        @foreach ($recentAppointments as $appointment)
                                            @php
                                                $appointmentStatusClass = match ($appointment->status) {
                                                    'completed' => 'success',
                                                    'confirmed' => 'primary',
                                                    'booked', 'scheduled' => 'info',
                                                    'cancelled' => 'danger',
                                                    'no_show' => 'warning',
                                                    default => 'secondary',
                                                };

                                                $customerName = trim(
                                                    ($appointment->customer?->first_name ?? '') .
                                                        ' ' .
                                                        ($appointment->customer?->last_name ?? ''),
                                                );

                                                $staffName = trim(
                                                    ($appointment->staff?->first_name ?? '') .
                                                        ' ' .
                                                        ($appointment->staff?->last_name ?? ''),
                                                );
                                            @endphp


                                            <tr>

                                                {{-- Date --}}
                                                <td>

                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-primary">
                                                                <i class="bi bi-calendar3 text-primary"></i>
                                                            </div>
                                                        </div>

                                                        <div>

                                                            <div class="fw-bold text-gray-900">
                                                                {{ $appointment->starts_at->format('d M') }}
                                                            </div>

                                                            <div class="text-muted fs-8">
                                                                {{ $appointment->starts_at->format('H:i') }}
                                                            </div>

                                                        </div>

                                                    </div>

                                                </td>


                                                {{-- Customer --}}
                                                <td>

                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $customerName ?: 'Walk-in / Unknown' }}
                                                    </div>

                                                </td>


                                                {{-- Staff --}}
                                                <td>

                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $staffName ?: 'Not Assigned' }}
                                                    </div>

                                                </td>


                                                {{-- Status --}}
                                                <td>

                                                    <span
                                                        class="badge badge-light-{{ $appointmentStatusClass }} px-3 py-2">
                                                        {{ str($appointment->status)->replace('_', ' ')->headline() }}
                                                    </span>

                                                </td>

                                            </tr>
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>
                        @else
                            <div class="text-center py-12">

                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-calendar-x text-muted fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Appointments
                                </h4>

                                <div class="text-muted fs-7">
                                    No appointments were recorded during this reporting period.
                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RECENT SALES --}}
            {{-- ===================================================== --}}
            <div class="col-xl-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-receipt-cutoff text-success fs-3"></i>
                                </div>
                            </div>

                            <div>

                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Recent Sales
                                </h2>

                                <div class="text-muted fs-8">
                                    Latest branch invoices from the selected period.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @if ($recentSales->isNotEmpty())

                            <div class="table-responsive">

                                <table class="table align-middle table-row-dashed gy-5">

                                    <thead>

                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th class="min-w-130px">Invoice</th>
                                            <th class="min-w-160px">Customer</th>
                                            <th class="min-w-130px">Total</th>
                                            <th class="min-w-110px">Status</th>
                                        </tr>

                                    </thead>


                                    <tbody class="fw-semibold text-gray-700">

                                        @foreach ($recentSales as $sale)
                                            @php
                                                $saleStatusClass = match ($sale->status) {
                                                    'completed', 'paid' => 'success',
                                                    'pending' => 'warning',
                                                    'cancelled', 'voided' => 'danger',
                                                    'refunded' => 'info',
                                                    default => 'secondary',
                                                };

                                                $saleCustomerName = trim(
                                                    ($sale->customer?->first_name ?? '') .
                                                        ' ' .
                                                        ($sale->customer?->last_name ?? ''),
                                                );
                                            @endphp


                                            <tr>

                                                {{-- Invoice --}}
                                                <td>

                                                    <div class="d-flex align-items-center">

                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-success">
                                                                <i class="bi bi-receipt text-success"></i>
                                                            </div>
                                                        </div>

                                                        <span class="fw-bold text-gray-900">
                                                            {{ $sale->invoice_number }}
                                                        </span>

                                                    </div>

                                                </td>


                                                {{-- Customer --}}
                                                <td>

                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $saleCustomerName ?: 'Walk-in Customer' }}
                                                    </div>

                                                </td>


                                                {{-- Total --}}
                                                <td>

                                                    <div class="fw-bolder text-gray-900">
                                                        {{ $branch->currency }}
                                                        {{ number_format((float) $sale->total, 2) }}
                                                    </div>

                                                </td>


                                                {{-- Status --}}
                                                <td>

                                                    <span class="badge badge-light-{{ $saleStatusClass }} px-3 py-2">
                                                        {{ str($sale->status)->replace('_', ' ')->headline() }}
                                                    </span>

                                                </td>

                                            </tr>
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>
                        @else
                            <div class="text-center py-12">

                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-receipt text-muted fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Sales
                                </h4>

                                <div class="text-muted fs-7">
                                    No sales were recorded during this reporting period.
                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>
