<x-default-layout>
    @section('title')
        Tax Report
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.reports.tax') }}
    @endsection

    @include('pages.apps.billing.reports._print-styles')

    @php
        $money = fn ($value) => 'LKR ' . number_format((float) $value, 2);
        $rateLabel = fn ($rate) => rtrim(rtrim(number_format($rate, 2), '0'), '.') . '%';
        $sscl = $report['sscl'];
    @endphp

    <div id="kt_app_content_container">
        <div class="d-flex justify-content-end mb-5">
            @include('partials.reports._export-buttons', ['route' => 'billing.reports.tax.download'])
        </div>

        <div class="card border-0 shadow-sm mb-8 no-print">
            <div class="card-body p-6">
                <form method="GET" action="{{ route('billing.reports.tax') }}" class="row g-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Month</label>
                        <input type="month" name="month" value="{{ $report['month']->format('Y-m') }}" class="form-control form-control-solid">
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
                        <a href="{{ route('billing.reports.tax.export', array_filter(['month' => $report['month']->format('Y-m'), 'branch_id' => $selectedBranch?->id])) }}" class="btn btn-light-success" title="Invoice list (CSV)">
                            <i class="bi bi-filetype-csv"></i>
                        </a>
                        <button type="button" class="btn btn-light-primary" onclick="window.print()" aria-label="Print">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-4 mb-8">
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-1">Tax Report - {{ $report['month']->format('F Y') }}</h1>
                        <div class="text-muted">{{ $tenant->name }} · {{ $selectedBranch?->name ?? 'All branches' }}</div>
                    </div>
                    <div class="text-md-end text-muted fs-7">
                        @if ($report['tax_number'])
                            <div>VAT / TIN: <span class="fw-bold text-gray-900">{{ $report['tax_number'] }}</span></div>
                        @else
                            <div class="no-print">Add your VAT number in Settings &gt; General.</div>
                        @endif
                        <div>{{ number_format($report['counts']['invoices']) }} invoices · {{ $report['counts']['credit_notes'] }} refunds · {{ $report['counts']['voided'] }} late voids</div>
                    </div>
                </div>

                <div class="row g-5 mb-10">
                    <div class="col-md-4">
                        <div class="rounded border p-5 h-100">
                            <div class="text-muted fs-8 mb-1">Taxable Supplies</div>
                            <div class="fw-bolder fs-3 text-gray-900">{{ $money($report['totals']['taxable']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded border p-5 h-100">
                            <div class="text-muted fs-8 mb-1">Output Tax Payable</div>
                            <div class="fw-bolder fs-3 text-primary">{{ $money($report['totals']['tax']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded border p-5 h-100">
                            <div class="text-muted fs-8 mb-1">{{ $sscl['enabled'] ? 'SSCL (' . $rateLabel($sscl['rate']) . ')' : 'Exempt / Zero-rated Supplies' }}</div>
                            <div class="fw-bolder fs-3 text-gray-900">{{ $sscl['enabled'] ? $money($sscl['levy']) : $money($report['exempt']['net']) }}</div>
                        </div>
                    </div>
                </div>

                <h4 class="fw-bold text-gray-900 mb-3">Output Tax by Rate</h4>
                <div class="table-responsive mb-10">
                    <table class="table table-row-dashed align-middle gy-3">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Tax</th>
                                <th class="text-end">Taxable Value</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Refunds</th>
                                <th class="text-end">Late Voids</th>
                                <th class="text-end">Net Taxable</th>
                                <th class="text-end">Net Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['rates'] as $row)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $row['name'] }} {{ $rateLabel($row['rate']) }}</td>
                                    <td class="text-end">{{ $money($row['taxable']) }}</td>
                                    <td class="text-end">{{ $money($row['tax']) }}</td>
                                    <td class="text-end text-danger">- {{ $money($row['credit_tax']) }}</td>
                                    <td class="text-end text-danger">- {{ $money($row['voided_tax']) }}</td>
                                    <td class="text-end">{{ $money($row['net_taxable']) }}</td>
                                    <td class="text-end fw-bold">{{ $money($row['net_tax']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-8">No taxed sales in this month.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($report['rates']->isNotEmpty())
                            <tfoot>
                                <tr class="fw-bolder border-top">
                                    <td>Total</td>
                                    <td colspan="4"></td>
                                    <td class="text-end">{{ $money($report['totals']['taxable']) }}</td>
                                    <td class="text-end">{{ $money($report['totals']['tax']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="row g-10">
                    <div class="col-lg-6">
                        <h4 class="fw-bold text-gray-900 mb-3">Other Supplies</h4>
                        <table class="table table-row-dashed align-middle gy-2">
                            <tbody>
                                <tr><td>Exempt / zero-rated sales</td><td class="text-end">{{ $money($report['exempt']['sales']) }}</td></tr>
                                <tr><td class="ps-6 text-muted">Less refunds &amp; late voids</td><td class="text-end text-danger">- {{ $money($report['exempt']['credit_notes'] + $report['exempt']['voided']) }}</td></tr>
                                <tr class="fw-bold"><td>Net exempt / zero-rated</td><td class="text-end">{{ $money($report['exempt']['net']) }}</td></tr>
                                <tr><td class="text-muted">Gift cards sold (taxed when spent)</td><td class="text-end text-muted">{{ $money($report['outside_scope']['gift_cards_sold']) }}</td></tr>
                                <tr><td class="text-muted">Tips passed to staff</td><td class="text-end text-muted">{{ $money($report['outside_scope']['tips']) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <h4 class="fw-bold text-gray-900 mb-3">SSCL Turnover</h4>
                        <table class="table table-row-dashed align-middle gy-2">
                            <tbody>
                                <tr><td>Services, packages &amp; memberships</td><td class="text-end">{{ $money($sscl['service_turnover']) }}</td></tr>
                                <tr><td>Retail products</td><td class="text-end">{{ $money($sscl['product_turnover']) }}</td></tr>
                                <tr class="fw-bold"><td>Turnover (excl. VAT)</td><td class="text-end">{{ $money($sscl['turnover']) }}</td></tr>
                                @if ($sscl['enabled'])
                                    <tr class="fw-bold"><td>SSCL at {{ $rateLabel($sscl['rate']) }}</td><td class="text-end text-primary">{{ $money($sscl['levy']) }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="text-muted fs-8">
                            @if ($sscl['enabled'])
                                Worked on the full turnover. Retail (product) sales may qualify for a reduced liable turnover; confirm with your accountant.
                            @else
                                Turn on "SSCL Registered" in Settings &gt; Sales &gt; Tax if the salon pays SSCL.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="text-muted fs-8 mt-8">
                    Invoices count in the month they were issued. Refunds count as credit notes in the month they were paid out, and an
                    invoice voided in a later month is reversed in that month, so a month you have already filed does not change.
                    Please have your accountant review these figures before filing.
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
