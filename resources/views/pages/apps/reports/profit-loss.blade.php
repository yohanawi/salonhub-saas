<x-default-layout>
    @section('title') Profit & Loss @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('reports.profit-loss') }} @endsection

    @php
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);
        $change = function ($current, $previous) {
            if ((float) $previous == 0.0) {
                return null;
            }
            return round(($current - $previous) / abs($previous) * 100, 1);
        };
        $delta = function ($current, $previous, $higherIsBetter = true) use ($change) {
            $value = $change($current, $previous);
            if ($value === null) {
                return '';
            }
            $good = $higherIsBetter ? $value >= 0 : $value <= 0;
            return '<span class="fs-8 fw-semibold text-' . ($good ? 'success' : 'danger') . '">' . ($value >= 0 ? '▲' : '▼') . ' ' . abs($value) . '%</span>';
        };
        $from = $report['from'];
        $to = $report['to'];
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Profit & Loss</h3>
                    <div class="text-muted fs-7">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }} · {{ $selectedBranch?->name ?? 'All branches' }} · compared with the previous {{ $from->diffInDays($to) + 1 > 1 ? (int) round($from->diffInDays($to) + 1) . ' days' : 'day' }}</div>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-end">
                    @include('pages/apps.reports._filters')
                    @include('partials.reports._export-buttons', ['route' => 'reports.profit-loss.export'])
                </div>
            </div>
        </div>

        <div class="row g-5 mb-5">
            @foreach ([
                ['label' => 'Net revenue', 'value' => $report['net_revenue'], 'prev' => $previous['net_revenue'], 'color' => 'primary', 'good' => true],
                ['label' => 'Gross profit', 'value' => $report['gross_profit'], 'prev' => $previous['gross_profit'], 'color' => 'info', 'good' => true, 'note' => $report['gross_margin'] . '% margin'],
                ['label' => 'Operating costs', 'value' => $report['operating_cost'], 'prev' => $previous['operating_cost'], 'color' => 'warning', 'good' => false],
                ['label' => 'Net profit', 'value' => $report['net_profit'], 'prev' => $previous['net_profit'], 'color' => $report['net_profit'] >= 0 ? 'success' : 'danger', 'good' => true, 'note' => $report['net_margin'] . '% margin'],
            ] as $tile)
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-5">
                            <div class="text-muted fs-8 mb-1">{{ $tile['label'] }}</div>
                            <div class="fs-2 fw-bolder text-{{ $tile['color'] }}">{{ $money($tile['value']) }}</div>
                            <div class="d-flex gap-2 align-items-center mt-1">
                                {!! $delta($tile['value'], $tile['prev'], $tile['good']) !!}
                                @isset($tile['note'])<span class="text-muted fs-8">{{ $tile['note'] }}</span>@endisset
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-5">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Statement</h3></div></div>
                    <div class="card-body pt-2">
                        <table class="table align-middle gy-3 fs-6">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th></th>
                                    <th class="text-end">This period</th>
                                    <th class="text-end">Previous</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="3" class="fw-bold text-gray-900 pt-4">Revenue</td></tr>
                                @foreach ($report['income'] as $index => $line)
                                    <tr><td class="ps-6">{{ $line['label'] }}</td><td class="text-end">{{ $money($line['amount']) }}</td><td class="text-end text-muted">{{ $money($previous['income'][$index]['amount'] ?? 0) }}</td></tr>
                                @endforeach
                                <tr><td class="ps-6">Less: refunds</td><td class="text-end text-danger">− {{ $money($report['refunds']) }}</td><td class="text-end text-muted">− {{ $money($previous['refunds']) }}</td></tr>
                                <tr class="fw-bold border-top"><td>Net revenue</td><td class="text-end">{{ $money($report['net_revenue']) }}</td><td class="text-end text-muted">{{ $money($previous['net_revenue']) }}</td></tr>

                                <tr><td colspan="3" class="fw-bold text-gray-900 pt-6">Cost of sales</td></tr>
                                <tr><td class="ps-6">Retail products sold (at cost)</td><td class="text-end">− {{ $money($report['cogs']['products']) }}</td><td class="text-end text-muted">− {{ $money($previous['cogs']['products']) }}</td></tr>
                                <tr><td class="ps-6">Service consumables used</td><td class="text-end">− {{ $money($report['cogs']['consumables']) }}</td><td class="text-end text-muted">− {{ $money($previous['cogs']['consumables']) }}</td></tr>
                                <tr class="fw-bold border-top"><td>Gross profit <span class="text-muted fs-8 fw-normal">({{ $report['gross_margin'] }}%)</span></td><td class="text-end">{{ $money($report['gross_profit']) }}</td><td class="text-end text-muted">{{ $money($previous['gross_profit']) }}</td></tr>

                                <tr><td colspan="3" class="fw-bold text-gray-900 pt-6">Expenses</td></tr>
                                @forelse ($report['expenses'] as $line)
                                    <tr><td class="ps-6">{{ $line['label'] }}</td><td class="text-end">− {{ $money($line['amount']) }}</td><td class="text-end text-muted">−</td></tr>
                                @empty
                                    <tr><td class="ps-6 text-muted" colspan="3">No confirmed expenses.</td></tr>
                                @endforelse

                                <tr><td colspan="3" class="fw-bold text-gray-900 pt-6">Staff</td></tr>
                                <tr><td class="ps-6">Salaries & wages (incl. commission)</td><td class="text-end">− {{ $money($report['staff']['payroll']) }}</td><td class="text-end text-muted">− {{ $money($previous['staff']['payroll']) }}</td></tr>
                                <tr><td class="ps-6">Employer EPF / ETF</td><td class="text-end">− {{ $money($report['staff']['statutory']) }}</td><td class="text-end text-muted">− {{ $money($previous['staff']['statutory']) }}</td></tr>
                                <tr><td class="ps-6">Commission paid outside payroll</td><td class="text-end">− {{ $money($report['staff']['commission_payouts']) }}</td><td class="text-end text-muted">− {{ $money($previous['staff']['commission_payouts']) }}</td></tr>

                                <tr class="fw-bolder fs-4 border-top border-2">
                                    <td>Net profit <span class="text-muted fs-8 fw-normal">({{ $report['net_margin'] }}%)</span></td>
                                    <td class="text-end text-{{ $report['net_profit'] >= 0 ? 'success' : 'danger' }}">{{ $money($report['net_profit']) }}</td>
                                    <td class="text-end text-muted fs-6">{{ $money($previous['net_profit']) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">How this is worked out</h3></div></div>
                    <div class="card-body pt-2 fs-7 text-gray-700">
                        <p><strong>Revenue</strong> is what was invoiced in the period, after discounts and before tax. Gift cards count when spent, not when sold.</p>
                        <p><strong>Cost of sales</strong> is the stock cost of products sold (less returns) and of consumables used by services.</p>
                        <p><strong>Expenses</strong> are confirmed expenses dated in the period.</p>
                        <p><strong>Staff</strong> is approved payroll for pay periods ending in the period (tips excluded: they belong to staff), employer EPF/ETF and commission payouts made outside payroll.</p>
                        <p class="mb-0 text-muted">Tax collected (not income): {{ $money($report['tax_collected']) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
