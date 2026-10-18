<x-default-layout>
    @section('title')
        Day-End Report
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.reports.day-end') }}
    @endsection

    @include('pages.apps.billing.reports._print-styles')

    @php
        $sales = $report['sales'];
        $money = fn ($value) => 'LKR ' . number_format((float) $value, 2);
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8 no-print">
            <div class="card-body p-6">
                <form method="GET" action="{{ route('billing.reports.day-end') }}" class="row g-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" value="{{ $report['day']->format('Y-m-d') }}" class="form-control form-control-solid">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select form-select-solid">
                            <option value="">All my branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-3">
                        <button type="submit" class="btn btn-primary flex-grow-1">Show Report</button>
                        <button type="button" class="btn btn-light-primary" onclick="window.print()" aria-label="Print">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-8 p-lg-10">
                <div class="mb-8">
                    <h1 class="fw-bolder text-gray-900 mb-1">Day-End Report</h1>
                    <div class="text-muted">
                        {{ $tenant->name }} · {{ $selectedBranch?->name ?? 'All branches' }} · {{ $report['day']->format('l, M d, Y') }}
                    </div>
                </div>

                <div class="row g-5 mb-10">
                    @foreach ([
                        ['Invoices', number_format($sales['invoices']), 'text-gray-900'],
                        ['Sales Total', $money($sales['total']), 'text-gray-900'],
                        ['Collected', $money($report['collected']), 'text-success'],
                        ['Refunded', $money($report['refunded']), 'text-danger'],
                        ['Net Collected', $money($report['net_collected']), 'text-primary'],
                        ['Drawer Over / Short', ($report['cash_difference'] > 0 ? '+' : '') . $money($report['cash_difference']), abs($report['cash_difference']) < 0.005 ? 'text-success' : 'text-danger'],
                    ] as [$label, $value, $class])
                        <div class="col-6 col-lg-4">
                            <div class="rounded border p-5 h-100">
                                <div class="text-muted fs-8 mb-1">{{ $label }}</div>
                                <div class="fw-bolder fs-3 {{ $class }}">{{ $value }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-10">
                    <div class="col-lg-6">
                        <h4 class="fw-bold text-gray-900 mb-3">Sales</h4>
                        <table class="table table-row-dashed align-middle gy-2">
                            <tbody>
                                <tr><td>Gross Sales</td><td class="text-end">{{ $money($sales['subtotal']) }}</td></tr>
                                <tr><td class="ps-6 text-muted">Promotion discounts</td><td class="text-end text-danger">- {{ $money($sales['promotion_discount']) }}</td></tr>
                                <tr><td class="ps-6 text-muted">Membership discounts</td><td class="text-end text-danger">- {{ $money($sales['membership_discount']) }}</td></tr>
                                <tr><td class="ps-6 text-muted">Line &amp; manual discounts</td><td class="text-end text-danger">- {{ $money($sales['other_discount']) }}</td></tr>
                                <tr><td class="ps-6 text-muted">Loyalty redemptions</td><td class="text-end text-danger">- {{ $money($sales['loyalty_redemption']) }}</td></tr>
                                <tr><td>Tax</td><td class="text-end">{{ $money($sales['tax']) }}</td></tr>
                                <tr class="fw-bold"><td>Sales Total</td><td class="text-end">{{ $money($sales['total']) }}</td></tr>
                            </tbody>
                        </table>

                        @if ($report['item_types']->isNotEmpty())
                            <h4 class="fw-bold text-gray-900 mb-3 mt-8">By Type</h4>
                            <table class="table table-row-dashed align-middle gy-2">
                                <tbody>
                                    @foreach ($report['item_types'] as $row)
                                        <tr>
                                            <td>{{ ucfirst($row['type']) }}s <span class="text-muted fs-8">× {{ $row['quantity'] }}</span></td>
                                            <td class="text-end">{{ $money($row['total']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if ($report['voided']['invoices'] > 0)
                            <div class="rounded bg-light-danger p-4 mt-6 fs-7">
                                {{ $report['voided']['invoices'] }} invoice(s) voided today, worth {{ $money($report['voided']['total']) }}.
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-6">
                        <h4 class="fw-bold text-gray-900 mb-3">Payments by Method</h4>
                        <table class="table table-row-dashed align-middle gy-2">
                            <tbody>
                                @forelse ($report['payments'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] }} <span class="text-muted fs-8">× {{ $row['count'] }}</span></td>
                                        <td class="text-end">{{ $money($row['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted" colspan="2">No payments.</td></tr>
                                @endforelse
                                <tr class="fw-bold"><td>Total Collected</td><td class="text-end">{{ $money($report['collected']) }}</td></tr>
                            </tbody>
                        </table>

                        <h4 class="fw-bold text-gray-900 mb-3 mt-8">Refunds by Method</h4>
                        <table class="table table-row-dashed align-middle gy-2">
                            <tbody>
                                @forelse ($report['refunds'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] }} <span class="text-muted fs-8">× {{ $row['count'] }}</span></td>
                                        <td class="text-end text-danger">- {{ $money($row['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted" colspan="2">No refunds.</td></tr>
                                @endforelse
                                <tr class="fw-bold"><td>Total Refunded</td><td class="text-end">- {{ $money($report['refunded']) }}</td></tr>
                            </tbody>
                        </table>

                        @if ($report['drawer_expenses'] > 0)
                            <div class="d-flex justify-content-between rounded bg-light p-4 mt-6 fs-7">
                                <span>Expenses paid from the cash drawer</span>
                                <span class="fw-bold text-danger">- {{ $money($report['drawer_expenses']) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="separator separator-dashed my-8"></div>

                <h4 class="fw-bold text-gray-900 mb-3">Cash Register Shifts</h4>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gy-3">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>Shift</th>
                                <th>Branch</th>
                                <th>Opened / Closed</th>
                                <th class="text-end">Expected</th>
                                <th class="text-end">Counted</th>
                                <th class="text-end">Over / Short</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['shifts'] as $shift)
                                @php($difference = (float) $shift->cash_difference)
                                <tr>
                                    <td><a href="{{ route('billing.cash-register.show', $shift) }}" class="fw-bold text-gray-900">{{ $shift->shift_number }}</a></td>
                                    <td>{{ $shift->branch?->name }}</td>
                                    <td class="fs-8">
                                        {{ $shift->opened_at->format('h:i A') }} {{ $shift->openedBy?->name }}
                                        <br>{{ $shift->closed_at ? $shift->closed_at->format('h:i A') . ' ' . $shift->closedBy?->name : 'Still open' }}
                                    </td>
                                    <td class="text-end">{{ $shift->isOpen() ? '—' : $money($shift->expected_cash) }}</td>
                                    <td class="text-end">{{ $shift->isOpen() ? '—' : $money($shift->counted_cash) }}</td>
                                    <td class="text-end {{ $shift->isOpen() ? '' : (abs($difference) < 0.005 ? 'text-success' : 'text-danger') }}">
                                        {{ $shift->isOpen() ? '—' : (abs($difference) < 0.005 ? 'Balanced' : ($difference > 0 ? '+' : '-') . ' ' . $money(abs($difference))) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No shifts on this day.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
