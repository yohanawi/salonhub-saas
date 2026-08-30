<x-default-layout>
    @section('title')
        Payments
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payments.index') }}
    @endsection

    <div id="kt_app_content_container">
        {{-- ========================================================= --}}
        {{-- PAGE HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="d-flex align-items-center justify-content-center rounded-4 bg-light-primary flex-shrink-0"
                            style="width: 52px; height: 52px;">
                            <i class="bi bi-credit-card-2-front text-primary fs-1"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Payments
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    Transactions
                                </span>
                            </div>
                            <div class="text-muted fs-7">
                                Review completed, voided and refunded payment transactions.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end">
                            <div class="text-muted fs-8 fw-semibold text-uppercase">
                                Total Records
                            </div>
                            <div class="fw-bolder text-gray-900 fs-2">
                                {{ number_format($payments->total()) }}
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-success"
                            style="width: 46px; height: 46px;">
                            <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- PAYMENT LEDGER --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Payment Ledger
                        </h3>
                        <div class="text-muted fs-7">
                            Transaction history recorded through the billing and POS workflow.
                        </div>
                    </div>
                </div>

                <div class="card-toolbar">
                    <span class="badge badge-light-primary px-4 py-3">
                        {{ number_format($payments->total()) }}
                        {{ Str::plural('Payment', $payments->total()) }}
                    </span>
                </div>
            </div>

            <div class="card-body pt-3 px-0 px-md-9 pb-8">
                @if ($payments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Payment</th>
                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-160px">Salon</th>
                                    @endif
                                    <th class="min-w-150px">Invoice</th>
                                    <th class="min-w-170px">Method</th>
                                    <th class="min-w-150px">Branch</th>
                                    <th class="min-w-130px">Status</th>
                                    <th class="text-end min-w-140px">Amount</th>
                                </tr>
                            </thead>

                            <tbody class="fw-semibold text-gray-700">
                                @foreach ($payments as $payment)
                                    @php
                                        $methodType = strtolower(
                                            (string) ($payment->method ?? ($payment->paymentMethod?->type ?? '')),
                                        );

                                        $methodConfig = match ($methodType) {
                                            'cash' => [
                                                'icon' => 'bi-cash-stack',
                                                'class' => 'success',
                                            ],

                                            'card' => [
                                                'icon' => 'bi-credit-card',
                                                'class' => 'primary',
                                            ],

                                            'bank', 'bank_transfer' => [
                                                'icon' => 'bi-bank',
                                                'class' => 'info',
                                            ],

                                            'online' => [
                                                'icon' => 'bi-globe2',
                                                'class' => 'warning',
                                            ],

                                            'wallet' => [
                                                'icon' => 'bi-wallet2',
                                                'class' => 'info',
                                            ],

                                            default => [
                                                'icon' => 'bi-credit-card-2-front',
                                                'class' => 'secondary',
                                            ],
                                        };

                                        $status = strtolower((string) $payment->status);

                                        $statusConfig = match ($status) {
                                            'completed', 'paid', 'success' => [
                                                'class' => 'success',
                                                'icon' => 'bi-check-circle-fill',
                                            ],

                                            'refunded' => [
                                                'class' => 'info',
                                                'icon' => 'bi-arrow-counterclockwise',
                                            ],

                                            'voided', 'cancelled' => [
                                                'class' => 'danger',
                                                'icon' => 'bi-x-circle-fill',
                                            ],

                                            'pending' => [
                                                'class' => 'warning',
                                                'icon' => 'bi-clock-fill',
                                            ],

                                            default => [
                                                'class' => 'secondary',
                                                'icon' => 'bi-circle-fill',
                                            ],
                                        };

                                        $methodName =
                                            $payment->paymentMethod?->name ??
                                            str($payment->method)->replace('_', ' ')->headline();

                                        $branchName =
                                            $payment->branch?->name ?? ($payment->invoice?->branch?->name ?? '—');
                                    @endphp

                                    <tr>
                                        {{-- Payment --}}
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-primary me-4 flex-shrink-0"
                                                    style="width: 46px; height: 46px;">
                                                    <i class="bi bi-receipt text-primary fs-3"></i>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-gray-900 fs-6">
                                                        {{ $payment->payment_number }}
                                                    </span>
                                                    <span class="text-muted fs-8 mt-1">
                                                        <i class="bi bi-clock me-1"></i>

                                                        {{ $payment->paid_at?->format('M d, Y · h:i A') ?? 'Not recorded' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>
                                                @if ($payment->tenant)
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-gray-900">
                                                            {{ $payment->tenant->name }}
                                                        </span>
                                                        <span class="text-muted fs-8">
                                                            Tenant
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif
                                            </td>
                                        @endif

                                        {{-- Invoice --}}
                                        <td>
                                            @if ($payment->invoice)
                                                <a href="{{ route('billing.invoices.show', $payment->invoice) }}"
                                                    class="d-inline-flex align-items-center gap-2 text-gray-900 text-hover-primary fw-bold">
                                                    <i class="bi bi-receipt-cutoff text-muted"></i>
                                                    {{ $payment->invoice->invoice_number }}
                                                </a>
                                                <div class="text-muted fs-8 mt-1">
                                                    Linked invoice
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Method --}}
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-{{ $methodConfig['class'] }} me-3 flex-shrink-0"
                                                    style="width: 36px; height: 36px;">
                                                    <i
                                                        class="bi {{ $methodConfig['icon'] }} text-{{ $methodConfig['class'] }}"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-gray-900">
                                                        {{ $methodName }}
                                                    </div>
                                                    @if ($payment->transaction_reference)
                                                        <div class="text-muted fs-8 mt-1">
                                                            Ref: {{ $payment->transaction_reference }}
                                                        </div>
                                                    @else
                                                        <div class="text-muted fs-8 mt-1">
                                                            Payment method
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Branch --}}
                                        <td>
                                            <span class="badge badge-light-info px-3 py-2">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                {{ $branchName }}
                                            </span>
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            <span class="badge badge-light-{{ $statusConfig['class'] }} px-3 py-2">
                                                <i class="bi {{ $statusConfig['icon'] }} me-1"></i>
                                                {{ $payment->status_label }}
                                            </span>
                                        </td>

                                        {{-- Amount --}}
                                        <td class="text-end">
                                            <div class="fw-bolder text-gray-900 fs-6">
                                                LKR {{ number_format((float) $payment->amount, 2) }}
                                            </div>
                                            <div class="text-muted fs-8 mt-1">
                                                Transaction amount
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ================================================= --}}
                    {{-- PAGINATION --}}
                    {{-- ================================================= --}}
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mt-8 px-2">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-bold text-gray-800">
                                {{ $payments->firstItem() ?? 0 }}
                            </span>
                            to
                            <span class="fw-bold text-gray-800">
                                {{ $payments->lastItem() ?? 0 }}
                            </span>
                            of
                            <span class="fw-bold text-gray-800">
                                {{ number_format($payments->total()) }}
                            </span>
                            payments
                        </div>
                        <div>
                            {{ $payments->withQueryString()->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-20 px-5">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-primary mx-auto mb-7"
                            style="width: 90px; height: 90px;">
                            <i class="bi bi-credit-card-2-front text-primary" style="font-size: 2.7rem;"></i>
                        </div>
                        <h2 class="fw-bolder text-gray-900 mb-3">
                            No payments found
                        </h2>
                        <div class="text-muted fs-6 mx-auto" style="max-width: 480px;">
                            Successful checkout payments, refunds and voided transactions
                            will appear here once billing activity begins.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
