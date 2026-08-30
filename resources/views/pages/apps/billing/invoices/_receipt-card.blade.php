@php
    $customerName = $invoice->customer?->full_name ?? 'Walk-in Customer';
    $hasBalance = (float) $invoice->balance_amount > 0;

    $paymentClass = match ($invoice->payment_status) {
        'paid' => 'success',
        'partially_paid' => 'warning',
        'refunded' => 'info',
        default => 'danger',
    };
@endphp

<div class="card border-0 shadow-sm mx-auto receipt-card" style="max-width: 560px;">
    <div class="card-body p-7 p-md-9">
        <div class="text-center mb-7">
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-primary mx-auto mb-4 receipt-logo"
                style="width: 64px; height: 64px;">
                <i class="bi bi-scissors text-primary fs-1"></i>
            </div>

            <h2 class="fw-bolder text-gray-900 mb-1">
                {{ $invoice->tenant?->name ?? 'Salon' }}
            </h2>

            @if ($invoice->branch?->name)
                <div class="fw-semibold text-gray-700">
                    {{ $invoice->branch->name }}
                </div>
            @endif

            @if ($invoice->branch?->address_summary)
                <div class="text-muted fs-8 mt-2">
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $invoice->branch->address_summary }}
                </div>
            @endif

            @if ($invoice->branch?->phone)
                <div class="text-muted fs-8 mt-1">
                    <i class="bi bi-telephone me-1"></i>
                    {{ $invoice->branch->phone }}
                </div>
            @endif
        </div>

        <div class="receipt-divider mb-6"></div>

        <div class="text-center mb-6">
            <div class="text-muted text-uppercase fw-bold fs-9 letter-spacing mb-1">
                Payment Receipt
            </div>
            <div class="fw-bolder text-gray-900 fs-3">
                {{ $invoice->invoice_number }}
            </div>
            <div class="mt-3">
                <span class="badge badge-light-{{ $paymentClass }} px-3 py-2">
                    {{ $invoice->payment_status_label }}
                </span>
            </div>
        </div>

        <div class="receipt-info-box mb-6">
            <div class="receipt-info-row">
                <span class="receipt-label">Date</span>
                <span class="receipt-value">
                    {{ $invoice->issued_at?->format('M d, Y h:i A') ?? '-' }}
                </span>
            </div>
            <div class="receipt-info-row">
                <span class="receipt-label">Customer</span>
                <span class="receipt-value">{{ $customerName }}</span>
            </div>
            @if ($invoice->appointment)
                <div class="receipt-info-row">
                    <span class="receipt-label">Appointment</span>
                    <span class="receipt-value">{{ $invoice->appointment->appointment_number }}</span>
                </div>
            @endif
            <div class="receipt-info-row">
                <span class="receipt-label">Branch</span>
                <span class="receipt-value">{{ $invoice->branch?->name ?? '-' }}</span>
            </div>
        </div>

        <div class="receipt-divider mb-6"></div>

        <div class="mb-6">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="fw-bold text-gray-900">Items</div>
                <div class="text-muted fs-8">
                    {{ $invoice->items->count() }}
                    {{ Str::plural('item', $invoice->items->count()) }}
                </div>
            </div>

            @forelse ($invoice->items as $item)
                <div class="receipt-item {{ ! $loop->last ? 'mb-5' : '' }}">
                    <div class="d-flex justify-content-between gap-4">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-gray-900">
                                {{ $item->item_name ?: $item->description }}
                            </div>
                            <div class="text-muted fs-8 mt-1">
                                {{ number_format((float) $item->quantity, 0) }}
                                x LKR {{ number_format((float) $item->unit_price, 2) }}
                                @if ($item->staff)
                                    <span class="mx-1">|</span>
                                    {{ $item->staff->full_name }}
                                @endif
                            </div>
                            @if ((float) $item->discount_amount > 0)
                                <div class="text-danger fs-8 mt-1">
                                    Discount: - LKR {{ number_format((float) $item->discount_amount, 2) }}
                                </div>
                            @endif
                        </div>

                        <div class="text-end flex-shrink-0">
                            <div class="fw-bolder text-gray-900">
                                LKR {{ number_format((float) $item->total_amount, 2) }}
                            </div>
                            <div class="text-muted fs-9 mt-1">
                                {{ str($item->item_type)->headline() }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted fs-8">No receipt items found.</div>
            @endforelse
        </div>

        <div class="receipt-divider my-6"></div>

        <div class="mb-6">
            <div class="receipt-total-row">
                <span>Subtotal</span>
                <span>LKR {{ number_format((float) $invoice->subtotal, 2) }}</span>
            </div>

            @if ((float) $invoice->discount > 0)
                <div class="receipt-total-row text-danger">
                    <span>Discount</span>
                    <span>- LKR {{ number_format((float) $invoice->discount, 2) }}</span>
                </div>
            @endif

            @if ((float) $invoice->promotion_discount_amount > 0)
                <div class="receipt-total-row text-danger">
                    <span>Promotion Discount</span>
                    <span>- LKR {{ number_format((float) $invoice->promotion_discount_amount, 2) }}</span>
                </div>
            @endif

            @if ((float) $invoice->membership_discount_amount > 0)
                <div class="receipt-total-row text-danger">
                    <span>Membership Discount</span>
                    <span>- LKR {{ number_format((float) $invoice->membership_discount_amount, 2) }}</span>
                </div>
            @endif

            @if ((float) $invoice->loyalty_redemption_amount > 0)
                <div class="receipt-total-row text-danger">
                    <span>Loyalty Redemption</span>
                    <span>- LKR {{ number_format((float) $invoice->loyalty_redemption_amount, 2) }}</span>
                </div>
            @endif

            @if ((float) $invoice->tax > 0)
                <div class="receipt-total-row">
                    <span>Tax</span>
                    <span>LKR {{ number_format((float) $invoice->tax, 2) }}</span>
                </div>
            @endif

            <div class="receipt-divider my-5"></div>

            <div class="d-flex justify-content-between align-items-end gap-4">
                <div>
                    <div class="fw-bolder text-gray-900 fs-5">Total</div>
                    <div class="text-muted fs-9">Final invoice amount</div>
                </div>
                <div class="fw-bolder text-gray-900 fs-3">
                    LKR {{ number_format((float) $invoice->total, 2) }}
                </div>
            </div>

            <div class="receipt-divider my-5"></div>

            <div class="receipt-total-row">
                <span class="fw-semibold text-success">Paid</span>
                <span class="fw-bolder text-success">
                    LKR {{ number_format((float) $invoice->paid_amount, 2) }}
                </span>
            </div>

            <div class="rounded-3 p-4 mt-4 {{ $hasBalance ? 'bg-light-danger' : 'bg-light-success' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fs-9 mb-1">Balance</div>
                        <div class="fw-bold {{ $hasBalance ? 'text-danger' : 'text-success' }}">
                            {{ $hasBalance ? 'Amount Outstanding' : 'Fully Settled' }}
                        </div>
                    </div>
                    <div class="fw-bolder {{ $hasBalance ? 'text-danger' : 'text-success' }} fs-4">
                        LKR {{ number_format((float) $invoice->balance_amount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        @if ($invoice->promotion_name)
            <div class="receipt-divider my-6"></div>
            <div class="rounded-3 bg-light-primary p-4 mb-6">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-tag-fill text-primary fs-4"></i>
                    <div>
                        <div class="text-muted fs-9">Promotion Applied</div>
                        <div class="fw-bold text-gray-900 mt-1">
                            {{ $invoice->promotion_name }}
                        </div>
                        @if ($invoice->promotion_coupon_code)
                            <div class="text-muted fs-8 mt-1">
                                Coupon:
                                <span class="fw-semibold">{{ $invoice->promotion_coupon_code }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="receipt-divider my-6"></div>

        <div class="mb-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="fw-bold text-gray-900">Payment</div>
                <div class="text-muted fs-9">
                    {{ $invoice->payments->count() }}
                    {{ Str::plural('transaction', $invoice->payments->count()) }}
                </div>
            </div>

            @forelse ($invoice->payments as $payment)
                @php
                    $methodName = $payment->paymentMethod?->name ?? str($payment->method)->headline();
                @endphp
                <div class="receipt-total-row">
                    <span class="fw-semibold">{{ $methodName }}</span>
                    <span class="fw-bolder">
                        LKR {{ number_format((float) $payment->amount, 2) }}
                    </span>
                </div>
                @if ($payment->transaction_reference)
                    <div class="text-muted fs-9 mb-3">
                        Ref: {{ $payment->transaction_reference }}
                    </div>
                @endif
            @empty
                <div class="text-muted fs-8">No payment transactions found.</div>
            @endforelse
        </div>

        <div class="receipt-divider my-6"></div>

        <div class="text-center">
            <div class="fw-bolder text-gray-900 mb-2">
                Thank you for visiting!
            </div>
            <div class="text-muted fs-8 mx-auto" style="max-width: 360px;">
                We appreciate your business and look forward to seeing you again.
            </div>
            <div class="mt-6 text-muted fs-9">
                Receipt generated for
                <span class="fw-semibold text-gray-700">
                    {{ $invoice->invoice_number }}
                </span>
            </div>
        </div>
    </div>
</div>
