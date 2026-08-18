<x-default-layout>
    @section('title')
        Invoice
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.invoices.show', $invoice) }}
    @endsection

    @php
        $paymentConfig = match ($invoice->payment_status) {
            'paid' => [
                'class' => 'success',
                'icon' => 'bi-check-circle-fill',
                'label' => $invoice->payment_status_label,
            ],
            'partially_paid' => [
                'class' => 'warning',
                'icon' => 'bi-circle-half',
                'label' => $invoice->payment_status_label,
            ],
            'refunded' => [
                'class' => 'info',
                'icon' => 'bi-arrow-counterclockwise',
                'label' => $invoice->payment_status_label,
            ],
            default => [
                'class' => 'danger',
                'icon' => 'bi-exclamation-circle-fill',
                'label' => $invoice->payment_status_label,
            ],
        };

        $customerName = $invoice->customer?->full_name ?? 'Walk-in Customer';

        $customerInitial = Str::upper(Str::substr($customerName, 0, 1));

        $hasBalance = (float) $invoice->balance_amount > 0;
    @endphp

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-8">
                <i class="bi bi-check-circle-fill fs-2 me-4"></i>
                <div class="fw-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-8">
                <i class="bi bi-exclamation-triangle-fill fs-2 me-4"></i>
                <div>
                    <div class="fw-bold mb-1">Something went wrong</div>
                    <div>{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-8">
                    <div class="d-flex align-items-start gap-5">
                        <div class="d-flex align-items-center justify-content-center rounded-4 bg-light-primary flex-shrink-0"
                            style="width: 52px; height: 52px;">
                            <i class="bi bi-receipt-cutoff text-primary fs-1"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $invoice->invoice_number }}
                                </h3>
                                <span class="badge badge-light-{{ $paymentConfig['class'] }} px-3 py-2">
                                    <i class="bi {{ $paymentConfig['icon'] }} me-1"></i>
                                    {{ $paymentConfig['label'] }}
                                </span>
                                <span class="badge badge-light px-3 py-2">
                                    {{ $invoice->status_label }}
                                </span>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-4 text-muted fs-7">
                                <span>
                                    <i class="bi bi-person me-1"></i>
                                    {{ $customerName }}
                                </span>
                                @if ($invoice->branch)
                                    <span>
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $invoice->branch->name }}
                                    </span>
                                @endif
                                @if ($invoice->appointment)
                                    <span>
                                        <i class="bi bi-calendar-check me-1"></i>
                                        {{ $invoice->appointment->appointment_number }}
                                    </span>
                                @endif
                                @if ($invoice->issued_at)
                                    <span>
                                        <i class="bi bi-clock me-1"></i>
                                        {{ $invoice->issued_at->format('M d, Y · h:i A') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex align-items-start flex-wrap gap-3">
                        <a href="#" class="btn btn-light-primary"
                            data-receipt-template="invoice-show-receipt-template"
                            data-receipt-invoice="{{ $invoice->invoice_number }}">
                            <i class="bi bi-printer"></i>                            
                        </a>
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-8">
                {{-- Invoice Items --}}
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Invoice Items
                                </h3>
                                <div class="text-muted fs-7">
                                    Services and products included in this transaction.
                                </div>
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary px-3 py-2">
                                {{ $invoice->items->count() }}
                                {{ Str::plural('Item', $invoice->items->count()) }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead>
                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                        <th class="min-w-200px">Item</th>
                                        <th class="min-w-150px">Staff</th>
                                        <th class="text-end min-w-70px">Qty</th>
                                        <th class="text-end min-w-110px">Unit</th>
                                        <th class="text-end min-w-110px">Discount</th>
                                        <th class="text-end min-w-120px">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @foreach ($invoice->items as $item)
                                        @php
                                            $itemTypeClass = match ($item->item_type) {
                                                'service' => 'primary',
                                                'product' => 'info',
                                                default => 'secondary',
                                            };

                                            $itemIcon = match ($item->item_type) {
                                                'service' => 'bi-scissors',
                                                'product' => 'bi-bag',
                                                default => 'bi-box',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-{{ $itemTypeClass }} me-4 flex-shrink-0"
                                                        style="width: 44px; height: 44px;">
                                                        <i
                                                            class="bi {{ $itemIcon }} text-{{ $itemTypeClass }} fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-gray-900 fs-6">
                                                            {{ $item->item_name ?: $item->description }}
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 mt-1">
                                                            <span class="badge badge-light-{{ $itemTypeClass }} fs-9">
                                                                {{ str($item->item_type)->headline() }}
                                                            </span> 
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($item->staff)
                                                    <div class="d-flex align-items-center"> 
                                                        <span class="fw-semibold text-gray-800">
                                                            {{ $item->staff->full_name }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="badge badge-light">
                                                    {{ $item->quantity }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-semibold text-gray-800">
                                                    LKR {{ number_format((float) $item->unit_price, 2) }}
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                @if ((float) $item->discount_amount > 0)
                                                    <div class="fw-semibold text-danger">
                                                        - LKR {{ number_format((float) $item->discount_amount, 2) }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bolder text-gray-900">
                                                    LKR {{ number_format((float) $item->total_amount, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Payment History
                                </h3>
                                <div class="text-muted fs-7">
                                    Payments recorded against this invoice.
                                </div>
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-success px-3 py-2">
                                {{ $invoice->payments->count() }}
                                {{ Str::plural('Payment', $invoice->payments->count()) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        @forelse ($invoice->payments as $payment)
                            @php
                                $paymentMethod = $payment->paymentMethod?->name ?? str($payment->method)->headline();
                                $paymentIcon = match ($payment->method) {
                                    'cash' => 'bi-cash-stack',
                                    'card' => 'bi-credit-card',
                                    'bank_transfer' => 'bi-bank',
                                    'online' => 'bi-globe2',
                                    default => 'bi-wallet2',
                                };
                            @endphp
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 py-5 {{ !$loop->last ? 'border-bottom' : '' }}">
                                {{-- Payment Details --}}
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-success me-4 flex-shrink-0"
                                        style="width: 46px; height: 46px;">
                                        <i class="bi {{ $paymentIcon }} text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <span class="fw-bold text-gray-900 fs-6">
                                                {{ $payment->payment_number }}
                                            </span>
                                            <span class="badge badge-light-success">
                                                {{ $payment->status_label }}
                                            </span>
                                        </div>
                                        <div class="text-muted fs-8 mt-2">
                                            <span>
                                                {{ $paymentMethod }}
                                            </span>
                                            @if ($payment->transaction_reference)
                                                <span class="mx-2">•</span>
                                                <span>
                                                    Ref:
                                                    <span class="fw-semibold text-gray-700">
                                                        {{ $payment->transaction_reference }}
                                                    </span>
                                                </span>
                                            @endif
                                            @if ($payment->paid_at)
                                                <span class="mx-2">•</span>
                                                <span>
                                                    {{ $payment->paid_at->format('M d, Y · h:i A') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-md-end">
                                    <div class="text-muted fs-8 mb-1">
                                        Amount Paid
                                    </div>
                                    <div class="fw-bolder text-success fs-5">
                                        LKR {{ number_format((float) $payment->amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-light mx-auto mb-5"
                                    style="width: 72px; height: 72px;">
                                    <i class="bi bi-credit-card text-muted fs-1"></i>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No payments recorded
                                </h4>
                                <div class="text-muted">
                                    Payment transactions for this invoice will appear here.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                {{-- Customer / Invoice Context --}}
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <h3 class="fw-bold text-gray-900 mb-0">
                                Invoice Details
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        {{-- Customer --}}
                        <div class="d-flex align-items-center mb-7">
                            <div class="symbol symbol-50px me-4">
                                <div class="symbol-label bg-light-primary text-primary fw-bolder fs-4">
                                    {{ $customerInitial }}
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8">
                                    Customer
                                </div>
                                <div class="fw-bold text-gray-900">
                                    {{ $customerName }}
                                </div>
                                <div class="text-muted fs-8">
                                    {{ $invoice->customer ? 'Registered Customer' : 'Walk-in Checkout' }}
                                </div>
                            </div>
                        </div>
                        <div class="separator separator-dashed mb-6"></div>
                        {{-- Branch --}}
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <div class="text-muted">
                                <i class="bi bi-geo-alt me-2"></i>
                                Branch
                            </div>
                            <div class="fw-semibold text-gray-900 text-end">
                                {{ $invoice->branch?->name ?? '—' }}
                            </div>
                        </div>

                        {{-- Appointment --}}
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <div class="text-muted">
                                <i class="bi bi-calendar-check me-2"></i>
                                Appointment
                            </div>
                            <div class="fw-semibold text-gray-900 text-end">
                                {{ $invoice->appointment?->appointment_number ?? 'Walk-in / Direct' }}
                            </div>
                        </div>

                        {{-- Issued --}}
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-muted">
                                <i class="bi bi-clock me-2"></i>
                                Issued
                            </div>
                            <div class="fw-semibold text-gray-900 text-end">
                                {{ $invoice->issued_at?->format('M d, Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Financial Summary
                                </h3>
                                <div class="text-muted fs-8">
                                    Invoice calculation breakdown.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        {{-- Subtotal --}}
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold text-gray-800">
                                LKR {{ number_format((float) $invoice->subtotal, 2) }}
                            </span>
                        </div>

                        {{-- Discount --}}
                        @if ((float) $invoice->discount > 0)
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="text-muted">
                                    Discount
                                </span>
                                <span class="fw-semibold text-danger">
                                    - LKR {{ number_format((float) $invoice->discount, 2) }}
                                </span>
                            </div>
                        @endif

                        {{-- Promotion --}}
                        @if ((float) $invoice->promotion_discount_amount > 0)
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="text-muted">
                                    Promotion
                                </span>
                                <span class="fw-semibold text-danger">
                                    - LKR {{ number_format((float) $invoice->promotion_discount_amount, 2) }}
                                </span>
                            </div>
                        @endif

                        {{-- Membership --}}
                        @if ((float) $invoice->membership_discount_amount > 0)
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="text-muted">
                                    Membership
                                </span>
                                <span class="fw-semibold text-danger">
                                    - LKR {{ number_format((float) $invoice->membership_discount_amount, 2) }}
                                </span>
                            </div>
                        @endif

                        {{-- Loyalty --}}
                        @if ((float) $invoice->loyalty_redemption_amount > 0)
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="text-muted">
                                    Loyalty Redemption
                                </span>
                                <span class="fw-semibold text-danger">
                                    - LKR {{ number_format((float) $invoice->loyalty_redemption_amount, 2) }}
                                </span>
                            </div>
                        @endif

                        {{-- Tax --}}
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <span class="text-muted">
                                Tax
                            </span>
                            <span class="fw-semibold text-gray-800">
                                LKR {{ number_format((float) $invoice->tax, 2) }}
                            </span>
                        </div>

                        <div class="separator separator-dashed my-5"></div>

                        {{-- Total --}}
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <div>
                                <div class="fw-bold text-gray-900 fs-6">
                                    Invoice Total
                                </div>
                                <div class="text-muted fs-8">
                                    Amount due
                                </div>
                            </div>

                            <div class="fw-bolder text-gray-900 fs-5">
                                LKR {{ number_format((float) $invoice->total, 2) }}
                            </div>
                        </div>

                        {{-- Paid --}}
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <span class="fw-semibold text-success">
                                Paid
                            </span>
                            <span class="fw-bolder text-success">
                                LKR {{ number_format((float) $invoice->paid_amount, 2) }}
                            </span>
                        </div>

                        {{-- Balance Box --}}
                        <div class="rounded-4 p-5 {{ $hasBalance ? 'bg-light-danger' : 'bg-light-success' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Remaining Balance
                                    </div>
                                    <div class="fw-bolder {{ $hasBalance ? 'text-danger' : 'text-success' }} fs-4">
                                        LKR {{ number_format((float) $invoice->balance_amount, 2) }}
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white"
                                    style="width: 42px; height: 42px;">
                                    <i
                                        class="bi {{ $hasBalance ? 'bi-exclamation-lg text-danger' : 'bi-check-lg text-success' }} fs-3"></i>
                                </div>
                            </div>
                        </div>

                        {{-- Promotion Snapshot --}}
                        @if ($invoice->promotion_name)
                            <div class="separator separator-dashed my-6"></div>
                            <div class="rounded-3 bg-light-primary p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-white me-3"
                                        style="width: 38px; height: 38px;">
                                        <i class="bi bi-tags text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8">
                                            Promotion Applied
                                        </div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $invoice->promotion_name }}
                                        </div>
                                    </div>
                                </div>

                                @if ($invoice->promotion_coupon_code)
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <span class="text-muted fs-8">
                                            Coupon Code
                                        </span>
                                        <span class="badge badge-light-primary px-3 py-2">
                                            {{ $invoice->promotion_coupon_code }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @can('void', $invoice)
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div>
                                    <h3 class="fw-bold text-danger mb-1">
                                        Void Invoice
                                    </h3>
                                    <div class="text-muted fs-8">
                                        Cancel this invoice with an audit reason.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="alert alert-warning border-0 d-flex align-items-start mb-5">
                                <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                                <div class="fs-8">
                                    Voiding an invoice may affect billing records,
                                    reports and financial reconciliation.
                                </div>
                            </div>
                            <form method="POST" action="{{ route('billing.invoices.void', $invoice) }}"
                                data-swal-confirm
                                data-swal-title="Void this invoice?"
                                data-swal-text="This will cancel the invoice and reverse related payments, stock, commissions, loyalty and promotion records."
                                data-swal-icon="warning"
                                data-swal-confirm-button="Yes, void invoice"
                                data-swal-cancel-button="Keep invoice">
                                @csrf
                                <div class="mb-5">
                                    <label class="form-label required fw-semibold">
                                        Reason
                                    </label>
                                    <textarea name="void_reason" class="form-control form-control-solid @error('void_reason') is-invalid @enderror"
                                        rows="4" required placeholder="Explain why this invoice is being voided...">{{ old('void_reason') }}</textarea>
                                    @error('void_reason')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <button class="btn btn-light-danger w-100" type="submit">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Void Invoice
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        <template id="invoice-show-receipt-template">
            @include('pages.apps.billing.invoices._receipt-card', ['invoice' => $invoice])
        </template>

        <div class="modal fade" id="invoiceShowReceiptModal" tabindex="-1" aria-hidden="true" data-receipt-modal>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h3 class="modal-title fw-bolder text-gray-900">
                                Receipt Preview
                            </h3>
                            <div class="text-muted fs-8" data-receipt-modal-subtitle>
                                {{ $invoice->invoice_number }}
                            </div>
                        </div>
                        <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="modal-body bg-light px-4 px-md-8 py-6" data-receipt-modal-body></div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="button" class="btn btn-light-primary" data-receipt-modal-print>
                            <i class="bi bi-printer-fill me-2"></i>
                            Print
                        </button> 
                    </div>
                </div>
            </div>
        </div>

        <iframe title="Receipt print frame" class="d-none" data-receipt-print-frame></iframe>
    </div>

    @include('pages.apps.billing._sweet-alerts')
    @include('pages.apps.billing.invoices._receipt-modal-assets')
</x-default-layout>
