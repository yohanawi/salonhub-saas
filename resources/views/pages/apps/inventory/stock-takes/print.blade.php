<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $stockTake->stock_take_number }} count sheet</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1e1e2d; margin: 32px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #7e8299; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px; border: 1px solid #c4c6d0; text-align: left; }
        th { background: #f5f8fa; font-size: 11px; text-transform: uppercase; }
        .box { width: 110px; }
        .center { text-align: center; }
        .sign { margin-top: 32px; display: flex; gap: 48px; }
        .sign div { border-top: 1px solid #1e1e2d; padding-top: 4px; width: 220px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>

<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('inventory.stock-takes.print', ['stockTake' => $stockTake, 'blind' => $blind ? 0 : 1]) }}">
            {{ $blind ? 'Show system quantities' : 'Hide system quantities (blind count)' }}
        </a>
    </div>

    <h1>Stock Count Sheet · {{ $stockTake->stock_take_number }}</h1>
    <div class="muted">
        {{ $stockTake->tenant?->name }} · {{ $stockTake->branch?->name }} · {{ $stockTake->category?->name ?? 'All products' }}
        · Started {{ $stockTake->started_at?->format('M d, Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU / Barcode</th>
                <th>Category</th>
                @unless ($blind)
                    <th class="center">System</th>
                @endunless
                <th class="center box">Counted</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stockTake->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ collect([$item->product?->sku, $item->product?->barcode])->filter()->implode(' / ') }}</td>
                    <td>{{ $item->product?->category?->name }}</td>
                    @unless ($blind)
                        <td class="center">{{ $item->system_quantity }}</td>
                    @endunless
                    <td class="box"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="sign">
        <div>Counted by</div>
        <div>Checked by</div>
        <div>Date</div>
    </div>
</body>

</html>
