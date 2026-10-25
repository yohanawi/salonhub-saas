@php
    $money = fn ($value) => number_format((float) $value, 2);
    $hasBalance = (float) $invoice->balance_amount > 0;
    $changeGiven = $invoice->payments->sum(fn ($payment) => (float) $payment->change_given);
    $cashReceived = $invoice->payments->sum(fn ($payment) => (float) ($payment->cash_received ?? 0));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $invoice->invoice_number }}</title>
    <style>
        /* 80mm paper has about 72mm of printable width on most counter printers. */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
        }

        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
        }

        .receipt {
            width: 72mm;
            margin: 0 auto;
            padding: 4mm 0 6mm;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .small {
            font-size: 10px;
        }

        .large {
            font-size: 15px;
        }

        .logo {
            display: block;
            max-width: 40mm;
            max-height: 18mm;
            margin: 0 auto 2mm;
            filter: grayscale(100%);
        }

        .rule {
            border: 0;
            border-top: 1px dashed #000000;
            margin: 2mm 0;
        }

        .rule-solid {
            border-top-style: solid;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 2mm;
        }

        .row > span:first-child {
            flex: 1 1 auto;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .row > span:last-child {
            flex: 0 0 auto;
            text-align: right;
        }

        .item {
            margin-bottom: 1.5mm;
        }

        .item-name {
            overflow-wrap: anywhere;
        }

        .void {
            border: 2px solid #000000;
            padding: 1mm;
            margin: 2mm 0;
            text-align: center;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .toolbar {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            font-family: system-ui, sans-serif;
        }

        .toolbar button {
            padding: 6px 14px;
            border: 1px solid #c4c4c4;
            border-radius: 6px;
            background: #f5f5f5;
            cursor: pointer;
        }

        @media screen {
            body {
                background: #eeeeee;
            }

            .receipt {
                background: #ffffff;
                padding: 4mm;
                width: 80mm;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
            }
        }

        @media print {
            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        @unless ($publicView ?? false)
            <button type="button" onclick="window.close()">Close</button>
        @endunless
    </div>

    <div class="receipt">
        <div class="center">
            @if ($showLogo)
                <img class="logo" src="{{ asset('storage/' . $invoice->tenant->logo_path) }}" alt="">
            @endif
            <div class="bold large">{{ $invoice->tenant?->name ?? 'Salon' }}</div>
            @if ($invoice->branch?->name)
                <div>{{ $invoice->branch->name }}</div>
            @endif
            @if (! empty($business['legal_name']))
                <div class="small">{{ $business['legal_name'] }}</div>
            @endif
            @if ($invoice->branch?->address_summary || ! empty($business['address']))
                <div class="small">{{ $invoice->branch?->address_summary ?: $business['address'] }}</div>
            @endif
            @if ($invoice->branch?->phone)
                <div class="small">Tel: {{ $invoice->branch->phone }}</div>
            @endif
            @if (! empty($business['registration_number']))
                <div class="small">Reg. No: {{ $business['registration_number'] }}</div>
            @endif
            @if (! empty($business['tax_number']))
                <div class="small">VAT: {{ $business['tax_number'] }}</div>
            @endif
            @if (! empty($business['website']))
                <div class="small">{{ $business['website'] }}</div>
            @endif
        </div>

        <hr class="rule">

        @if ($invoice->status === \App\Models\Invoice::STATUS_VOID)
            <div class="void">VOID</div>
        @endif

        <div class="row"><span>Invoice</span><span class="bold">{{ $invoice->invoice_number }}</span></div>
        <div class="row"><span>Date</span><span>{{ $invoice->issued_at?->format('d/m/Y h:i A') ?? '-' }}</span></div>
        @if ($showCustomer)
            <div class="row"><span>Customer</span><span>{{ $invoice->customer?->full_name ?? 'Walk-in' }}</span></div>
            @if ($invoice->customer?->phone)
                <div class="row"><span>Phone</span><span>{{ $invoice->customer->phone }}</span></div>
            @endif
        @endif
        @if ($invoice->appointment)
            <div class="row"><span>Appointment</span><span>{{ $invoice->appointment->appointment_number }}</span></div>
        @endif
        @if ($invoice->cashier)
            <div class="row"><span>Cashier</span><span>{{ $invoice->cashier->full_name }}</span></div>
        @endif

        <hr class="rule">

        @foreach ($invoice->items as $item)
            <div class="item">
                <div class="item-name bold">{{ $item->item_name ?: $item->description }}</div>
                <div class="row">
                    <span>{{ number_format((float) $item->quantity, 0) }} x {{ $money($item->unit_price) }}</span>
                    <span>{{ $money($item->total_amount) }}</span>
                </div>
                @if ((float) $item->discount_amount > 0)
                    <div class="row small"><span>Discount</span><span>-{{ $money($item->discount_amount) }}</span></div>
                @endif
                @if ($item->staff)
                    <div class="small">By {{ $item->staff->full_name }}</div>
                @endif
            </div>
        @endforeach

        <hr class="rule">

        <div class="row"><span>Subtotal</span><span>{{ $money($invoice->subtotal) }}</span></div>
        @if ((float) $invoice->discount > 0)
            <div class="row"><span>Discount</span><span>-{{ $money($invoice->discount) }}</span></div>
        @endif
        @if ((float) $invoice->package_discount_amount > 0)
            <div class="row"><span>Package Credits</span><span>-{{ $money($invoice->package_discount_amount) }}</span></div>
        @endif
        @if ((float) $invoice->loyalty_redemption_amount > 0)
            <div class="row"><span>Loyalty</span><span>-{{ $money($invoice->loyalty_redemption_amount) }}</span></div>
        @endif
        @if ((float) $invoice->tax > 0)
            <div class="row">
                <span>{{ $invoice->tax_name ?: 'Tax' }}{{ $invoice->tax_inclusive ? ' (incl.)' : '' }}</span>
                <span>{{ $money($invoice->tax) }}</span>
            </div>
        @endif

        <hr class="rule rule-solid">
        <div class="row bold large"><span>TOTAL LKR</span><span>{{ $money($invoice->total) }}</span></div>
        @if ((float) $invoice->tip_amount > 0)
            <div class="row"><span>Tip</span><span>{{ $money($invoice->tip_amount) }}</span></div>
            <div class="row bold"><span>Amount Due</span><span>{{ $money($invoice->amountDue()) }}</span></div>
        @endif
        <hr class="rule rule-solid">

        @foreach ($invoice->payments as $payment)
            <div class="row">
                <span>{{ $payment->paymentMethod?->name ?? str($payment->method)->headline() }}</span>
                <span>{{ $money($payment->amount) }}</span>
            </div>
            @if ($payment->giftCard)
                <div class="small">Card {{ $payment->giftCard->code }} - Bal {{ $money($payment->giftCard->balance) }}</div>
            @elseif ($payment->transaction_reference)
                <div class="small">Ref: {{ $payment->transaction_reference }}</div>
            @endif
        @endforeach
        @if ($cashReceived > 0)
            <div class="row"><span>Cash Received</span><span>{{ $money($cashReceived) }}</span></div>
        @endif
        @if ($changeGiven > 0)
            <div class="row bold"><span>Change</span><span>{{ $money($changeGiven) }}</span></div>
        @endif
        @if ((float) $invoice->refunded_amount > 0)
            <div class="row"><span>Refunded</span><span>-{{ $money($invoice->refunded_amount) }}</span></div>
        @endif
        <div class="row bold"><span>{{ $hasBalance ? 'Balance Due' : 'Balance' }}</span><span>{{ $money($invoice->balance_amount) }}</span></div>

        @if ($invoice->promotion_name)
            <hr class="rule">
            <div class="small">Promotion: {{ $invoice->promotion_name }}@if ($invoice->promotion_coupon_code) ({{ $invoice->promotion_coupon_code }})@endif</div>
        @endif

        <hr class="rule">

        <div class="center">
            <div class="bold">{{ $footerMessage ?: 'Thank you for visiting!' }}</div>
            @if ($terms)
                <div class="small" style="margin-top: 2mm; white-space: pre-line;">{{ $terms }}</div>
            @endif
            <div class="small" style="margin-top: 2mm;">{{ now()->format('d/m/Y h:i A') }}</div>
        </div>
    </div>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function() {
                window.focus();
                window.print();
            });
        </script>
    @endif
</body>
</html>
