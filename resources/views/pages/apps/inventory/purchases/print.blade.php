<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $purchase->purchase_number }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1e1e2d; margin: 32px; font-size: 13px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #7e8299; }
        .row { display: flex; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 8px; border-bottom: 1px solid #e4e6ef; text-align: left; }
        th { background: #f5f8fa; font-size: 11px; text-transform: uppercase; color: #5e6278; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .grand { border-top: 2px solid #1e1e2d; font-weight: bold; font-size: 15px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>

<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 16px;"><button onclick="window.print()">Print</button></div>

    <div class="row">
        <div>
            <h1>Purchase Order</h1>
            <div><strong>{{ $purchase->purchase_number }}</strong></div>
            <div class="muted">Date: {{ $purchase->purchase_date?->format('M d, Y') }}</div>
            @if ($purchase->expected_date)
                <div class="muted">Expected delivery: {{ $purchase->expected_date->format('M d, Y') }}</div>
            @endif
        </div>
        <div style="text-align: right;">
            <div><strong>{{ $purchase->tenant?->name }}</strong></div>
            <div>Deliver to: {{ $purchase->branch?->name }}</div>
            <div class="muted">{{ $purchase->branch?->address }}</div>
            <div class="muted">{{ $purchase->branch?->phone }}</div>
        </div>
    </div>

    <div class="row">
        <div>
            <div class="muted">Supplier</div>
            <div><strong>{{ $purchase->supplier?->name ?? '—' }}</strong></div>
            @if ($purchase->supplier)
                <div>{{ $purchase->supplier->contact_person }}</div>
                <div class="muted">{{ collect([$purchase->supplier->phone, $purchase->supplier->email])->filter()->implode(' · ') }}</div>
                <div class="muted">{{ $purchase->supplier->address }}</div>
            @endif
        </div>
        @if ($purchase->invoice_number)
            <div style="text-align: right;">
                <div class="muted">Supplier reference</div>
                <div>{{ $purchase->invoice_number }}</div>
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th class="right">Qty</th>
                <th class="right">Unit Cost</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->product?->sku }}</td>
                    <td class="right">{{ $item->quantity }} {{ $item->product?->unit?->symbol }}</td>
                    <td class="right">{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><span>{{ number_format((float) $purchase->subtotal, 2) }}</span></div>
        <div><span>Discount</span><span>- {{ number_format((float) $purchase->discount, 2) }}</span></div>
        <div><span>Tax</span><span>{{ number_format((float) $purchase->tax, 2) }}</span></div>
        <div class="grand"><span>Total ({{ $purchase->tenant?->currency ?: 'LKR' }})</span><span>{{ number_format((float) $purchase->total, 2) }}</span></div>
    </div>

    @if ($purchase->notes)
        <p class="muted" style="margin-top: 24px; white-space: pre-line;">{{ $purchase->notes }}</p>
    @endif
</body>

</html>
