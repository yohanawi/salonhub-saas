<x-default-layout>
    @section('title') Branch Comparison @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('reports.branch-comparison') }} @endsection

    @php
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 0);
        $from = $report['from'];
        $to = $report['to'];
        $rows = $report['rows'];
        $leader = fn ($key, $branch) => ($report['leaders'][$key] ?? null) === $branch->id && $rows->count() > 1;
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Branch Comparison</h3>
                    <div class="text-muted fs-7">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }} · {{ $rows->count() }} {{ str('branch')->plural($rows->count()) }}</div>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-end">
                    @include('pages/apps.reports._filters')
                    @include('partials.reports._export-buttons', ['route' => 'reports.branch-comparison.export'])
                </div>
            </div>
        </div>

        <div class="row g-5 mb-5">
            @foreach ($rows as $row)
                <div class="col-md-6 col-xxl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold text-gray-900 mb-1">{{ $row['branch']->name }}</h3>
                                <div class="d-flex flex-wrap gap-1">
                                    @if ($leader('net_revenue', $row['branch']))<span class="badge badge-light-primary">Top revenue</span>@endif
                                    @if ($leader('net_profit', $row['branch']))<span class="badge badge-light-success">Most profitable</span>@endif
                                    @if ($leader('average_bill', $row['branch']))<span class="badge badge-light-info">Highest avg bill</span>@endif
                                    @if ($leader('appointments', $row['branch']))<span class="badge badge-light-warning">Busiest</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-2 fs-7">
                            <div class="text-muted fs-8">Net revenue</div>
                            <div class="fs-3 fw-bolder text-gray-900">{{ $money($row['net_revenue']) }}</div>
                            <div class="progress h-6px mb-4"><div class="progress-bar bg-primary" style="width: {{ max(0, round($row['net_revenue'] / $report['max']['net_revenue'] * 100)) }}%"></div></div>

                            <div class="text-muted fs-8">Net profit ({{ $row['net_margin'] }}%)</div>
                            <div class="fs-4 fw-bold text-{{ $row['net_profit'] >= 0 ? 'success' : 'danger' }}">{{ $money($row['net_profit']) }}</div>
                            <div class="progress h-6px mb-5"><div class="progress-bar bg-{{ $row['net_profit'] >= 0 ? 'success' : 'danger' }}" style="width: {{ round(abs($row['net_profit']) / $report['max']['net_profit'] * 100) }}%"></div></div>

                            <div class="row g-3">
                                <div class="col-6"><div class="text-muted fs-8">Invoices</div><div class="fw-bold">{{ number_format($row['invoices']) }}</div></div>
                                <div class="col-6"><div class="text-muted fs-8">Average bill</div><div class="fw-bold">{{ $money($row['average_bill']) }}</div></div>
                                <div class="col-6"><div class="text-muted fs-8">Appointments</div><div class="fw-bold">{{ number_format($row['appointments']) }} <span class="text-muted fw-normal">({{ $row['completed'] }} done)</span></div></div>
                                <div class="col-6"><div class="text-muted fs-8">No-show rate</div><div class="fw-bold">{{ $row['no_show_rate'] }}%</div></div>
                                <div class="col-6"><div class="text-muted fs-8">New customers</div><div class="fw-bold">{{ number_format($row['new_customers']) }}</div></div>
                                <div class="col-6"><div class="text-muted fs-8">Rating</div><div class="fw-bold">{{ $row['rating'] ? $row['rating'] . ' ★ (' . $row['rating_count'] . ')' : '—' }}</div></div>
                                <div class="col-6"><div class="text-muted fs-8">Expenses</div><div class="fw-bold">{{ $money($row['expenses']) }}</div></div>
                                <div class="col-6"><div class="text-muted fs-8">Staff cost</div><div class="fw-bold">{{ $money($row['staff_cost']) }}</div></div>
                                <div class="col-12"><div class="text-muted fs-8">Top service</div><div class="fw-bold">{{ $row['top_service'] ?? '—' }}</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Side by side</h3></div></div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-3 fs-7">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>Metric</th>
                                @foreach ($rows as $row)<th class="text-end">{{ $row['branch']->name }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                'net_revenue' => ['Net revenue', true], 'gross_profit' => ['Gross profit', true], 'expenses' => ['Expenses', true], 'staff_cost' => ['Staff cost', true], 'net_profit' => ['Net profit', true],
                                'invoices' => ['Invoices', false], 'average_bill' => ['Average bill', true], 'appointments' => ['Appointments', false], 'no_show_rate' => ['No-show %', false], 'new_customers' => ['New customers', false],
                            ] as $key => [$label, $isMoney])
                                <tr>
                                    <td class="fw-semibold">{{ $label }}</td>
                                    @foreach ($rows as $row)
                                        <td class="text-end">{{ $isMoney ? $money($row[$key]) : number_format((float) $row[$key], $key === 'no_show_rate' ? 1 : 0) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
