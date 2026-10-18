<x-default-layout>
    @section('title')
        Sales Report
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.reports.sales') }}
    @endsection

    @include('pages.apps.billing.reports._print-styles')

    @php
        $summary = $report['summary'];
        $money = fn ($value) => 'LKR ' . number_format((float) $value, 2);
        $query = array_filter([
            'from' => $report['from']->format('Y-m-d'),
            'to' => $report['to']->format('Y-m-d'),
            'branch_id' => $selectedBranch?->id,
        ]);
        $exportUrl = fn (string $type) => route('billing.reports.sales.export', $query + ['type' => $type]);
        $maxMonth = max(1, (float) $report['months']->max('net_sales'));
        $tabs = [
            'months' => 'By Month',
            'staff' => 'By Staff',
            'services' => 'By Service',
            'products' => 'By Product',
            'payments' => 'Payments',
            'prepaid' => 'Gift Cards & Deposits',
        ];
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8 no-print">
            <div class="card-body p-6">
                <form method="GET" action="{{ route('billing.reports.sales') }}" class="row g-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">From</label>
                        <input type="date" name="from" value="{{ $report['from']->format('Y-m-d') }}" class="form-control form-control-solid @error('from') is-invalid @enderror">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">To</label>
                        <input type="date" name="to" value="{{ $report['to']->format('Y-m-d') }}" class="form-control form-control-solid @error('to') is-invalid @enderror">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select form-select-solid">
                            <option value="">All my branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-3">
                        <button type="submit" class="btn btn-primary flex-grow-1">Show Report</button>
                        <button type="button" class="btn btn-light-primary" onclick="window.print()" aria-label="Print">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
                    @if ($errors->any())
                        <div class="col-12 text-danger fs-7">{{ $errors->first() }}</div>
                    @endif
                    <div class="col-12 d-flex flex-wrap gap-2">
                        @php($today = now($tenant->timezone ?: config('app.timezone')))
                        @foreach ([
                            'This month' => [$today->copy()->startOfMonth(), $today],
                            'Last month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
                            'Last 3 months' => [$today->copy()->subMonthsNoOverflow(2)->startOfMonth(), $today],
                            'This year' => [$today->copy()->startOfYear(), $today],
                        ] as $label => [$start, $end])
                            <a href="{{ route('billing.reports.sales', array_filter(['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'), 'branch_id' => $selectedBranch?->id])) }}" class="btn btn-sm btn-light">{{ $label }}</a>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-8 p-lg-10">
                <div class="mb-8">
                    <h1 class="fw-bolder text-gray-900 mb-1">Sales Report</h1>
                    <div class="text-muted">
                        {{ $tenant->name }} · {{ $selectedBranch?->name ?? 'All branches' }} ·
                        {{ $report['from']->format('M d, Y') }} - {{ $report['to']->format('M d, Y') }}
                    </div>
                </div>

                <div class="row g-5 mb-10">
                    @foreach ([
                        ['Net Sales', $money($summary['net_sales']), 'text-primary', 'Excluding tax and gift cards'],
                        ['Invoices', number_format($summary['invoices']), 'text-gray-900', $summary['invoices'] > 0 ? 'Avg ' . $money($summary['net_sales'] / $summary['invoices']) : ''],
                        ['Discounts', $money($summary['discounts']), 'text-danger', $summary['package_credits'] > 0 ? 'Incl. ' . $money($summary['package_credits']) . ' package credits' : 'From gross ' . $money($summary['gross'])],
                        ['Tax Collected', $money($summary['tax']), 'text-gray-900', ''],
                        ['Tips for Staff', $money($summary['tips']), 'text-gray-900', 'Not part of sales'],
                        ['Refunds', $money($summary['refunds']), 'text-danger', 'Paid out in the period'],
                    ] as [$label, $value, $class, $hint])
                        <div class="col-6 col-lg-4">
                            <div class="rounded border p-5 h-100">
                                <div class="text-muted fs-8 mb-1">{{ $label }}</div>
                                <div class="fw-bolder fs-3 {{ $class }}">{{ $value }}</div>
                                @if ($hint)
                                    <div class="text-muted fs-9 mt-1">{{ $hint }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <ul class="nav nav-tabs nav-line-tabs fs-6 mb-6 no-print" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" href="#sales-tab-{{ $key }}" role="tab">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    {{-- By month --}}
                    <div class="tab-pane fade show active" id="sales-tab-months" role="tabpanel">
                        @include('pages.apps.billing.reports._export-link', ['url' => $exportUrl('months')])
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gy-3">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Month</th>
                                        <th class="text-end">Invoices</th>
                                        <th class="text-end">Services</th>
                                        <th class="text-end">Products</th>
                                        <th class="text-end">Packages</th>
                                        <th class="text-end">Net Sales</th>
                                        <th class="text-end">Tax</th>
                                        <th class="text-end">Tips</th>
                                        <th class="text-end">Refunds</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['months'] as $row)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-gray-900">{{ $row['label'] }}</div>
                                                <div class="bg-light-primary rounded mt-1" style="height: 4px;">
                                                    <div class="bg-primary rounded h-100" style="width: {{ round($row['net_sales'] / $maxMonth * 100) }}%;"></div>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ number_format($row['invoices']) }}</td>
                                            <td class="text-end">{{ $money($row['services']) }}</td>
                                            <td class="text-end">{{ $money($row['products']) }}</td>
                                            <td class="text-end">{{ $money($row['other']) }}</td>
                                            <td class="text-end fw-bold">{{ $money($row['net_sales']) }}</td>
                                            <td class="text-end">{{ $money($row['tax']) }}</td>
                                            <td class="text-end">{{ $money($row['tips']) }}</td>
                                            <td class="text-end text-danger">{{ $money($row['refunds']) }}</td>
                                        </tr>
                                    @empty
                                        @include('pages.apps.billing.reports._empty-row', ['columns' => 9])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- By staff --}}
                    <div class="tab-pane fade" id="sales-tab-staff" role="tabpanel">
                        @include('pages.apps.billing.reports._export-link', ['url' => $exportUrl('staff')])
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gy-3">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Staff</th>
                                        <th class="text-end">Services Done</th>
                                        <th class="text-end">Service Sales</th>
                                        <th class="text-end">Product Sales</th>
                                        <th class="text-end">Total Sales</th>
                                        <th class="text-end">Tips</th>
                                        <th class="text-end">Commission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['staff'] as $row)
                                        <tr>
                                            <td class="fw-bold text-gray-900">{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['services_count']) }}</td>
                                            <td class="text-end">{{ $money($row['service_sales']) }}</td>
                                            <td class="text-end">{{ $money($row['product_sales']) }}</td>
                                            <td class="text-end fw-bold">{{ $money($row['total_sales']) }}</td>
                                            <td class="text-end">{{ $money($row['tips']) }}</td>
                                            <td class="text-end">{{ $money($row['commission']) }}</td>
                                        </tr>
                                    @empty
                                        @include('pages.apps.billing.reports._empty-row', ['columns' => 7])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- By service / product --}}
                    @foreach (['services' => 'Service', 'products' => 'Product'] as $key => $label)
                        <div class="tab-pane fade" id="sales-tab-{{ $key }}" role="tabpanel">
                            @include('pages.apps.billing.reports._export-link', ['url' => $exportUrl($key)])
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle gy-3">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                            <th>{{ $label }}</th>
                                            <th class="text-end">Quantity</th>
                                            @if ($key === 'services')
                                                <th class="text-end">Paid by Package</th>
                                            @endif
                                            <th class="text-end">Gross</th>
                                            <th class="text-end">Net Sales</th>
                                            <th class="text-end">Average</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($report[$key] as $row)
                                            <tr>
                                                <td class="fw-bold text-gray-900">{{ $row['name'] }}</td>
                                                <td class="text-end">{{ number_format($row['quantity']) }}</td>
                                                @if ($key === 'services')
                                                    <td class="text-end">{{ number_format($row['package_quantity']) }}</td>
                                                @endif
                                                <td class="text-end">{{ $money($row['gross']) }}</td>
                                                <td class="text-end fw-bold">{{ $money($row['net']) }}</td>
                                                <td class="text-end">{{ $money($row['average']) }}</td>
                                            </tr>
                                        @empty
                                            @include('pages.apps.billing.reports._empty-row', ['columns' => $key === 'services' ? 6 : 5])
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    {{-- Payments --}}
                    <div class="tab-pane fade" id="sales-tab-payments" role="tabpanel">
                        @include('pages.apps.billing.reports._export-link', ['url' => $exportUrl('payments')])
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gy-3">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Method</th>
                                        <th class="text-end">Transactions</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['payments'] as $row)
                                        <tr>
                                            <td class="fw-bold text-gray-900">{{ $row['name'] }}</td>
                                            <td class="text-end">{{ number_format($row['count']) }}</td>
                                            <td class="text-end fw-bold">{{ $money($row['total']) }}</td>
                                        </tr>
                                    @empty
                                        @include('pages.apps.billing.reports._empty-row', ['columns' => 3])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="text-muted fs-8">Gift card and deposit lines are value spent from earlier prepayments, not new money taken.</div>
                    </div>

                    {{-- Prepaid --}}
                    <div class="tab-pane fade" id="sales-tab-prepaid" role="tabpanel">
                        <table class="table table-row-dashed align-middle gy-3 mw-600px">
                            <tbody>
                                <tr><td>Gift cards sold</td><td class="text-end">{{ $money($summary['gift_cards_sold']) }}</td></tr>
                                <tr><td>Gift card value spent</td><td class="text-end">{{ $money($report['prepaid']['gift_cards_redeemed']) }}</td></tr>
                                <tr class="fw-bold"><td>Unspent gift card balance (today)</td><td class="text-end">{{ $money($report['prepaid']['gift_card_liability']) }}</td></tr>
                                <tr><td>Deposits received</td><td class="text-end">{{ $money($report['prepaid']['deposits_received']) }}</td></tr>
                                <tr><td>Deposits kept by the salon (forfeited)</td><td class="text-end">{{ $money($report['prepaid']['deposits_forfeited']) }}</td></tr>
                                <tr class="fw-bold"><td>Deposits still held (today)</td><td class="text-end">{{ $money($report['prepaid']['deposits_held']) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-muted fs-8 mt-8">
                    Sales are net of tax and of all discounts, with invoice-level discounts shared across the lines of each invoice.
                    Voided invoices are excluded. Refunds are shown in the month they were paid out and are not deducted from line sales.
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
