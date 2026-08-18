<x-default-layout>
    @section('title')
        Invoice
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.invoices.show', $invoice) }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-8">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-8">{{ $errors->first() }}</div>
        @endif

        @php
            $paymentClass = match ($invoice->payment_status) {
                'paid' => 'success',
                'partially_paid' => 'warning',
                'refunded' => 'info',
                default => 'danger',
            };
        @endphp

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h1 class="fw-bolder text-gray-900 mb-0">{{ $invoice->invoice_number }}</h1>
                            <span class="badge badge-light-{{ $paymentClass }} px-3 py-2">{{ $invoice->payment_status_label }}</span>
                            <span class="badge badge-light">{{ $invoice->status_label }}</span>
                        </div>
                        <div class="text-muted mt-2">
                            {{ $invoice->customer?->full_name ?? 'Walk-in Customer' }} · {{ $invoice->branch?->name }}
                            @if ($invoice->appointment)
                                · {{ $invoice->appointment->appointment_number }}
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-3 flex-wrap align-self-start">
                        <a href="{{ route('billing.invoices.receipt', $invoice) }}" class="btn btn-light-primary">
                            <i class="bi bi-printer me-2"></i>Receipt
                        </a>
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-light">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <h3 class="fw-bold text-gray-900 mb-0">Invoice Items</h3>
                    </div>
                    <div class="card-body pt-4">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Item</th>
                                        <th>Staff</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Unit</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @foreach ($invoice->items as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-gray-900">{{ $item->item_name ?: $item->description }}</div>
                                                <div class="text-muted fs-8">{{ str($item->item_type)->headline() }}</div>
                                            </td>
                                            <td>{{ $item->staff?->full_name ?? '-' }}</td>
                                            <td class="text-end">{{ $item->quantity }}</td>
                                            <td class="text-end">LKR {{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td class="text-end">LKR {{ number_format((float) $item->discount_amount, 2) }}</td>
                                            <td class="text-end fw-bold">LKR {{ number_format((float) $item->total_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <h3 class="fw-bold text-gray-900 mb-0">Payment History</h3>
                    </div>
                    <div class="card-body pt-4">
                        @forelse ($invoice->payments as $payment)
                            <div class="d-flex justify-content-between align-items-start border-bottom py-4">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $payment->payment_number }}</div>
                                    <div class="text-muted fs-8">
                                        {{ $payment->paymentMethod?->name ?? str($payment->method)->headline() }}
                                        @if ($payment->transaction_reference)
                                            · Ref: {{ $payment->transaction_reference }}
                                        @endif
                                        · {{ $payment->paid_at?->format('M d, Y h:i A') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-gray-900">LKR {{ number_format((float) $payment->amount, 2) }}</div>
                                    <span class="badge badge-light-success">{{ $payment->status_label }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">No payments recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <h3 class="fw-bold text-gray-900 mb-0">Financial Summary</h3>
                    </div>
                    <div class="card-body pt-4">
                        @foreach ([
                            'Subtotal' => $invoice->subtotal,
                            'Discount' => $invoice->discount,
                            'Promotion Discount' => $invoice->promotion_discount_amount,
                            'Membership Discount' => $invoice->membership_discount_amount,
                            'Loyalty Redemption' => $invoice->loyalty_redemption_amount,
                            'Tax' => $invoice->tax,
                            'Total' => $invoice->total,
                            'Paid' => $invoice->paid_amount,
                            'Balance' => $invoice->balance_amount,
                        ] as $label => $amount)
                            <div class="d-flex justify-content-between mb-3 {{ $label === 'Total' || $label === 'Balance' ? 'fw-bold text-gray-900' : 'text-muted' }}">
                                <span>{{ $label }}</span>
                                <span>LKR {{ number_format((float) $amount, 2) }}</span>
                            </div>
                        @endforeach
                        @if ($invoice->promotion_name)
                            <div class="separator separator-dashed my-5"></div>
                            <div class="text-muted fs-8 mb-1">Promotion Snapshot</div>
                            <div class="fw-bold text-gray-900">{{ $invoice->promotion_name }}</div>
                            @if ($invoice->promotion_coupon_code)
                                <div class="text-muted">Coupon {{ $invoice->promotion_coupon_code }}</div>
                            @endif
                        @endif
                    </div>
                </div>

                @can('void', $invoice)
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-8">
                            <h3 class="fw-bold text-gray-900 mb-0">Void Invoice</h3>
                        </div>
                        <div class="card-body pt-4">
                            <form method="POST" action="{{ route('billing.invoices.void', $invoice) }}">
                                @csrf
                                <textarea name="void_reason" class="form-control form-control-solid mb-4" rows="3" required placeholder="Void reason"></textarea>
                                <button class="btn btn-light-danger w-100" type="submit">Void Invoice</button>
                            </form>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-default-layout>
