{{-- Drawer reconciliation and takings by method for one shift. Expects $summary. --}}
<div class="row g-5 mb-8">
    @foreach ([
        ['Opening Cash', $summary['opening_cash'], 'text-gray-900'],
        ['Cash Sales', $summary['cash_sales'], 'text-success'],
        ['Cash Refunds', -$summary['cash_refunds'], 'text-danger'],
        ['Cash In', $summary['cash_in'], 'text-success'],
        ['Cash Out', -$summary['cash_out'], 'text-danger'],
        ['Expenses Paid', -($summary['cash_expenses'] ?? 0), 'text-danger'],
        ['Expected in Drawer', $summary['expected_cash'], 'text-primary'],
    ] as [$label, $value, $class])
        <div class="col-6 col-md-3">
            <div class="rounded border p-4 h-100">
                <div class="text-muted fs-8 mb-1">{{ $label }}</div>
                <div class="fw-bolder fs-5 {{ $class }}">LKR {{ number_format($value, 2) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-8">
    <div class="col-md-6">
        <h4 class="fw-bold text-gray-900 mb-3">Payments by Method</h4>
        <table class="table table-row-dashed align-middle gy-2 mb-0">
            <tbody>
                @forelse ($summary['payments'] as $row)
                    <tr>
                        <td>{{ $row['name'] }} <span class="text-muted fs-8">× {{ $row['count'] }}</span></td>
                        <td class="text-end fw-semibold">LKR {{ number_format($row['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="text-muted" colspan="2">No payments yet.</td></tr>
                @endforelse
                <tr class="fw-bold">
                    <td>Total Taken</td>
                    <td class="text-end">LKR {{ number_format($summary['payments_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <h4 class="fw-bold text-gray-900 mb-3">Refunds by Method</h4>
        <table class="table table-row-dashed align-middle gy-2 mb-0">
            <tbody>
                @forelse ($summary['refunds'] as $row)
                    <tr>
                        <td>{{ $row['name'] }} <span class="text-muted fs-8">× {{ $row['count'] }}</span></td>
                        <td class="text-end fw-semibold text-danger">- LKR {{ number_format($row['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="text-muted" colspan="2">No refunds.</td></tr>
                @endforelse
                <tr class="fw-bold">
                    <td>Total Refunded</td>
                    <td class="text-end">- LKR {{ number_format($summary['refunds_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
