<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Payslip {{ $item->staff?->full_name }}</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #1e1e2d; font-size: 11px; margin: 0; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color: #7e8299; }
        .header { border-bottom: 2px solid #1e1e2d; padding-bottom: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 3px 0; vertical-align: top; }
        .lines th { background: #f5f8fa; text-align: left; padding: 6px; font-size: 10px; text-transform: uppercase; color: #5e6278; }
        .lines td { padding: 6px; border-bottom: 1px solid #eff2f5; }
        .right { text-align: right; }
        .total td { font-weight: bold; border-top: 1px solid #1e1e2d; }
        .net { margin-top: 16px; background: #e8fff3; padding: 10px; font-size: 14px; font-weight: bold; }
        .employer { margin-top: 14px; font-size: 10px; }
        .footer { margin-top: 24px; font-size: 9px; color: #7e8299; }
    </style>
</head>

<body>
    @php $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2); @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>{{ $item->tenant?->name }}</h1>
                    <div class="muted">{{ $item->branch?->name }}</div>
                </td>
                <td class="right">
                    <h1>PAYSLIP</h1>
                    <div class="muted">{{ $item->period?->name }}</div>
                    <div class="muted">{{ $item->period?->start_date?->format('d M Y') }} – {{ $item->period?->end_date?->format('d M Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info">
        <tr>
            <td style="width: 50%;">
                <div class="muted">Employee</div>
                <div><strong>{{ $item->staff?->full_name }}</strong></div>
                <div>{{ $item->staff?->job_title }}</div>
                <div class="muted">Employee code: {{ $item->staff?->employee_code ?? '—' }}</div>
            </td>
            <td style="width: 50%;" class="right">
                @if ($item->salaryStructure?->epf_number)
                    <div class="muted">EPF No: {{ $item->salaryStructure->epf_number }}</div>
                @endif
                @if ($item->salaryStructure?->bank_account_number)
                    <div class="muted">Bank: {{ $item->salaryStructure->bank_name }} · {{ $item->salaryStructure->bank_account_number }}</div>
                @endif
                <div class="muted">Pay date: {{ $item->period?->pay_date?->format('d M Y') ?? '—' }}</div>
                <div class="muted">Status: {{ str($item->payment_status)->headline() }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <table class="lines">
                    <tr><th>Earnings</th><th class="right">Amount</th></tr>
                    @foreach ($earnings->groupBy('category') as $category => $lines)
                        <tr>
                            <td>{{ str($category)->replace('_', ' ')->headline() }}{{ $lines->count() > 1 ? " ({$lines->count()})" : '' }}</td>
                            <td class="right">{{ number_format((float) $lines->sum('amount'), 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total"><td>Gross Earnings</td><td class="right">{{ number_format((float) $item->gross_pay, 2) }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                <table class="lines">
                    <tr><th>Deductions</th><th class="right">Amount</th></tr>
                    @forelse ($deductions->groupBy('category') as $category => $lines)
                        <tr>
                            <td>{{ $category === 'epf_employee' ? 'EPF (Employee)' : str($category)->replace('_', ' ')->headline() }}</td>
                            <td class="right">{{ number_format((float) $lines->sum('amount'), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="muted">No deductions</td></tr>
                    @endforelse
                    <tr class="total"><td>Total Deductions</td><td class="right">{{ number_format((float) $item->total_deductions, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="net">
        <table><tr><td>NET PAY</td><td class="right">{{ $money($item->net_pay) }}</td></tr></table>
    </div>

    @if ((float) $item->epf_employer_amount > 0 || (float) $item->etf_employer_amount > 0)
        <div class="employer">
            <strong>Employer contributions</strong> (not deducted from your pay):
            EPF {{ $money($item->epf_employer_amount) }} · ETF {{ $money($item->etf_employer_amount) }}
            · EPF/ETF earnings {{ $money($item->statutory_earnings) }}
        </div>
    @endif

    <div class="footer">
        Generated {{ now()->format('d M Y H:i') }} · {{ $item->run?->run_number }} · This is a computer-generated payslip.
    </div>
</body>

</html>
