<x-default-layout>
    @section('title')
        Receipt
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.invoices.receipt', $invoice) }}
    @endsection

    <div id="kt_app_content_container">
        <div class="d-flex justify-content-end mb-6 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer me-2"></i>Print Receipt
            </button>
        </div>

        <div class="card border-0 shadow-sm mx-auto receipt-card" style="max-width: 520px;">
            <div class="card-body p-8">
                <div class="text-center mb-6">
                    <h2 class="fw-bolder text-gray-900 mb-1">{{ $invoice->tenant?->name }}</h2>
                    <div class="text-muted">{{ $invoice->branch?->name }}</div>
                    <div class="text-muted">{{ $invoice->branch?->address_summary }}</div>
                    <div class="text-muted">{{ $invoice->branch?->phone }}</div>
                </div>

                <div class="separator separator-dashed mb-6"></div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Invoice</span>
                    <span class="fw-bold">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Date</span>
                    <span>{{ $invoice->issued_at?->format('M d, Y h:i A') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Customer</span>
                    <span>{{ $invoice->customer?->full_name ?? 'Walk-in' }}</span>
                </div>
                @if ($invoice->appointment)
                    <div class="d-flex justify-content-between mb-6">
                        <span class="text-muted">Appointment</span>
                        <span>{{ $invoice->appointment->appointment_number }}</span>
                    </div>
                @endif

                <div class="separator separator-dashed mb-5"></div>

                @foreach ($invoice->items as $item)
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <div class="fw-semibold">{{ $item->item_name ?: $item->description }}</div>
                            <div class="text-muted fs-8">{{ $item->staff?->full_name }}</div>
                        </div>
                        <div class="text-end">LKR {{ number_format((float) $item->total_amount, 2) }}</div>
                    </div>
                @endforeach

                <div class="separator separator-dashed my-5"></div>

                @foreach ([
                    'Subtotal' => $invoice->subtotal,
                    'Discount' => $invoice->discount,
                    'Promotion Discount' => $invoice->promotion_discount_amount,
                    'Membership Discount' => $invoice->membership_discount_amount,
                    'Loyalty Redemption' => $invoice->loyalty_redemption_amount,
                    'Tax' => $invoice->tax,
                    'TOTAL' => $invoice->total,
                    'Paid' => $invoice->paid_amount,
                    'Balance' => $invoice->balance_amount,
                ] as $label => $amount)
                    <div class="d-flex justify-content-between mb-2 {{ $label === 'TOTAL' ? 'fw-bolder fs-5 text-gray-900' : '' }}">
                        <span>{{ $label }}</span>
                        <span>LKR {{ number_format((float) $amount, 2) }}</span>
                    </div>
                @endforeach

                <div class="separator separator-dashed my-5"></div>

                @foreach ($invoice->payments as $payment)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $payment->paymentMethod?->name ?? str($payment->method)->headline() }}</span>
                        <span>LKR {{ number_format((float) $payment->amount, 2) }}</span>
                    </div>
                @endforeach

                <div class="text-center text-muted mt-8">Thank you for visiting!</div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            @media print {
                .no-print,
                #kt_app_sidebar,
                #kt_app_header,
                #kt_app_toolbar {
                    display: none !important;
                }

                .receipt-card {
                    box-shadow: none !important;
                    border: 0 !important;
                }
            }
        </style>
    @endpush
</x-default-layout>
