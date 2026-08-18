<x-default-layout>
    @section('title')
        Payments
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payments.index') }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10">
                <h1 class="fw-bolder text-gray-900 mb-1">Payments</h1>
                <div class="text-muted">Review completed, voided and refunded payment records.</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body pt-6">
                @if ($payments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Payment</th>
                                    @if ($isSuperAdmin ?? false)
                                        <th>Salon</th>
                                    @endif
                                    <th>Invoice</th>
                                    <th>Method</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $payment->payment_number }}</div>
                                            <div class="text-muted fs-8">{{ $payment->paid_at?->format('M d, Y h:i A') }}</div>
                                        </td>
                                        @if ($isSuperAdmin ?? false)
                                            <td>{{ $payment->tenant?->name ?? '-' }}</td>
                                        @endif
                                        <td>
                                            @if ($payment->invoice)
                                                <a href="{{ route('billing.invoices.show', $payment->invoice) }}" class="text-hover-primary">
                                                    {{ $payment->invoice->invoice_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $payment->paymentMethod?->name ?? str($payment->method)->headline() }}</td>
                                        <td>{{ $payment->branch?->name ?? $payment->invoice?->branch?->name ?? '-' }}</td>
                                        <td><span class="badge badge-light-success">{{ $payment->status_label }}</span></td>
                                        <td class="text-end fw-bold">LKR {{ number_format((float) $payment->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8">{{ $payments->links() }}</div>
                @else
                    <div class="text-center py-15">
                        <i class="bi bi-credit-card text-muted" style="font-size: 3rem;"></i>
                        <h3 class="fw-bold text-gray-900 mt-5">No payments found</h3>
                        <div class="text-muted">Checkout payments will appear here.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
