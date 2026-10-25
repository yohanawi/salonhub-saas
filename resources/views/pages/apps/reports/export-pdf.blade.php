<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #1e1e2d; font-size: 10px; margin: 0; }
        h1 { font-size: 17px; margin: 0; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
        .muted { color: #7e8299; }
        .header { border-bottom: 2px solid #1e1e2d; padding-bottom: 8px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 8px 2px 0; }
        .summary { margin: 8px 0; }
        .summary td { border: 1px solid #e4e6ef; padding: 6px 8px; width: 25%; vertical-align: top; }
        .summary .label { color: #7e8299; font-size: 9px; }
        .summary .value { font-size: 12px; font-weight: bold; }
        .data th { background: #f1f1f4; text-align: left; padding: 5px; font-size: 9px; text-transform: uppercase; color: #5e6278; }
        .data td { padding: 5px; border-bottom: 1px solid #eff2f5; }
        .data .total td { font-weight: bold; border-top: 1px solid #1e1e2d; }
        .right { text-align: right; }
        .footer { margin-top: 18px; font-size: 8px; color: #7e8299; }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $document->title }}</h1>
        <div class="muted">{{ $document->salonName }}</div>
    </div>

    @if ($document->meta)
        <table class="meta">
            @foreach ($document->meta as $label => $value)
                <tr><td class="muted">{{ $label }}</td><td><strong>{{ $value }}</strong></td></tr>
            @endforeach
        </table>
    @endif

    @if ($document->summary)
        <table class="summary">
            @foreach (array_chunk($document->summary, 4, true) as $chunk)
                <tr>
                    @foreach ($chunk as $label => $value)
                        <td><div class="label">{{ $label }}</div><div class="value">{{ $value }}</div></td>
                    @endforeach
                    @for ($i = count($chunk); $i < 4; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif

    @foreach ($document->tables as $table)
        <h2>{{ $table->title }}</h2>
        <table class="data">
            <thead>
                <tr>
                    @foreach ($table->headings as $index => $heading)
                        <th class="{{ in_array($index, $table->moneyColumns, true) ? 'right' : '' }}">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($table->rows as $row)
                    <tr>
                        @foreach ($row as $index => $value)
                            <td class="{{ in_array($index, $table->moneyColumns, true) ? 'right' : '' }}">{{ in_array($index, $table->moneyColumns, true) ? $money($value) : $value }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($table->headings) }}" class="muted">No data for this period.</td></tr>
                @endforelse
                @if ($table->totals)
                    <tr class="total">
                        @foreach ($table->totals as $index => $value)
                            <td class="{{ in_array($index, $table->moneyColumns, true) ? 'right' : '' }}">{{ in_array($index, $table->moneyColumns, true) ? $money($value) : $value }}</td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

    <div class="footer">Generated {{ now()->format('d M Y H:i') }} · Amounts in {{ $document->currency }}</div>
</body>

</html>
