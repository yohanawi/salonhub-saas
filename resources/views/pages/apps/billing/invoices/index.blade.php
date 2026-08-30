<x-default-layout>
    @section('title')
        Invoices
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.invoices.index') }}
    @endsection

    <div id="kt_app_content_container">

        {{-- Flash Message --}}
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-8">
                <i class="bi bi-check-circle-fill fs-2 me-4"></i>

                <div class="fw-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- HERO / PAGE HEADER --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-8">
                    <div class="d-flex align-items-start gap-5">
                        <div class="d-flex align-items-center justify-content-center rounded-4 bg-light-primary"
                            style="width: 52px; height: 52px;">
                            <i class="bi bi-receipt-cutoff text-primary fs-1"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Invoices
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    Billing
                                </span>
                            </div>
                            <div class="text-gray-600 fs-7">
                                Monitor checkout invoices, payments and outstanding balances.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end">
                            <div class="text-muted fs-8 fw-semibold text-uppercase">
                                Total Records
                            </div>
                            <div class="fw-bolder text-gray-900 fs-2">
                                {{ number_format($invoices->total()) }}
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
        {{-- FILTER PANEL --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Find an Invoice
                        </h3>
                        <div class="text-muted fs-7">
                            Search and narrow invoices using the filters below.
                        </div>
                    </div>
                </div>
                @if (request()->hasAny(['search', 'tenant_id', 'branch_id', 'payment_status', 'date']))
                    <div class="card-toolbar">
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-sm btn-light-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            Clear Filters
                        </a>
                    </div>
                @endif
            </div>

            <div class="card-body pt-5">
                <form method="GET" action="{{ route('billing.invoices.index') }}">
                    <div class="row g-5 align-items-end">
                        {{-- Search --}}
                        <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label fw-semibold text-gray-700">
                                Search
                            </label>
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute text-muted"
                                    style="top: 50%; left: 16px; transform: translateY(-50%);"></i>
                                <input type="search" name="search" value="{{ request('search') }}"
                                    class="form-control form-control-solid ps-12" placeholder="Invoice, customer...">
                            </div>
                        </div>

                        {{-- Tenant --}}
                        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label fw-semibold text-gray-700">
                                    Salon
                                </label>
                                <select name="tenant_id" class="form-select form-select-solid" data-control="select2"
                                    data-placeholder="All salons">
                                    <option value="">All salons</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Branch --}}
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="form-label fw-semibold text-gray-700">
                                Branch
                            </label>
                            <select name="branch_id" class="form-select form-select-solid" data-control="select2"
                                data-placeholder="All branches">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Payment Status --}}
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="form-label fw-semibold text-gray-700">
                                Payment
                            </label>
                            <select name="payment_status" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All statuses</option>
                                @foreach ($paymentStatuses as $status)
                                    <option value="{{ $status }}" @selected(request('payment_status') === $status)>
                                        {{ str($status)->replace('_', ' ')->headline() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date --}}
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="form-label fw-semibold text-gray-700">
                                Issue Date
                            </label>
                            <input type="date" name="date" value="{{ request('date') }}"
                                class="form-control form-control-solid">
                        </div>

                        {{-- Submit --}}
                        <div class="col-12 col-xl-1">
                            <button type="submit" class="btn btn-primary w-100" data-bs-toggle="tooltip"
                                title="Apply filters">
                                <i class="bi bi-funnel-fill"></i>
                                <span class="d-xl-none ms-2">
                                    Filter
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- INVOICE LIST --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-7">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Invoice Register
                        </h3>
                        <div class="text-muted fs-7">
                            Checkout and payment history across your salon.
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary px-4 py-3">
                        {{ number_format($invoices->total()) }}
                        {{ Str::plural('Invoice', $invoices->total()) }}
                    </span>
                </div>
            </div>

            <div class="card-body pt-3 px-0 px-md-9 pb-8">
                @if ($invoices->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                                    <th class="min-w-175px">Invoice</th>

                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-150px">Salon</th>
                                    @endif

                                    <th class="min-w-180px">Customer</th>
                                    <th class="min-w-130px">Branch</th>
                                    <th class="min-w-130px">Payment</th>

                                    <th class="text-end min-w-120px">
                                        Total
                                    </th>

                                    <th class="text-end min-w-120px">
                                        Paid
                                    </th>

                                    <th class="text-end min-w-120px">
                                        Balance
                                    </th>

                                    <th class="text-end min-w-175px">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @foreach ($invoices as $invoice)
                                    @php
                                        $paymentConfig = match ($invoice->payment_status) {
                                            'paid' => [
                                                'class' => 'success',
                                                'icon' => 'bi-check-circle-fill',
                                            ],

                                            'partially_paid' => [
                                                'class' => 'warning',
                                                'icon' => 'bi-circle-half',
                                            ],

                                            'refunded' => [
                                                'class' => 'info',
                                                'icon' => 'bi-arrow-counterclockwise',
                                            ],

                                            default => [
                                                'class' => 'danger',
                                                'icon' => 'bi-exclamation-circle-fill',
                                            ],
                                        };
                                        $customerName = $invoice->customer?->full_name ?? 'Walk-in Customer';
                                        $customerInitial = Str::upper(Str::substr($customerName, 0, 1));
                                        $showUrl = route(
                                            'billing.invoices.show',
                                            ['invoice' => $invoice->getKey()],
                                            false,
                                        );
                                    @endphp

                                    <tr>
                                        {{-- Invoice --}}
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-primary me-4"
                                                    style="width: 44px; height: 44px;">
                                                    <i class="bi bi-receipt text-primary fs-4"></i>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <a href="{{ $showUrl }}"
                                                        class="text-gray-900 text-hover-primary fw-bold fs-6">
                                                        {{ $invoice->invoice_number }}
                                                    </a> 
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-gray-800">
                                                        {{ $invoice->tenant?->name ?? '—' }}
                                                    </span>
                                                    <span class="text-muted fs-8">
                                                        Salon
                                                    </span>
                                                </div>
                                            </td>
                                        @endif

                                        {{-- Customer --}}
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-gray-900">
                                                    {{ $customerName }}
                                                </span>
                                                @if ($invoice->customer)
                                                    <span class="text-muted fs-8">
                                                        Registered
                                                    </span>
                                                @else
                                                    <span class="text-muted fs-8">
                                                        Walk-in checkout
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Branch --}}
                                        <td>
                                            <span class="badge badge-light-info px-3 py-2">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                {{ $invoice->branch?->name ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- Payment Status --}}
                                        <td>
                                            <span
                                                class="badge badge-light-{{ $paymentConfig['class'] }} px-3 py-2 d-flex align-items-center">
                                                <i class="bi {{ $paymentConfig['icon'] }} me-1"></i>
                                                {{ $invoice->payment_status_label }}
                                            </span>
                                        </td>

                                        {{-- Total --}}
                                        <td class="text-end">
                                            <div class="fw-bold text-gray-900">
                                                LKR {{ number_format((float) $invoice->total, 2) }}
                                            </div>
                                            <div class="text-muted fs-8">
                                                Invoice total
                                            </div>
                                        </td>

                                        {{-- Paid --}}
                                        <td class="text-end">
                                            <div class="fw-bold text-success">
                                                LKR {{ number_format((float) $invoice->paid_amount, 2) }}
                                            </div>
                                            <div class="text-muted fs-8">
                                                Collected
                                            </div>
                                        </td>

                                        {{-- Balance --}}
                                        <td class="text-end">
                                            @if ((float) $invoice->balance_amount > 0)
                                                <div class="fw-bolder text-danger">
                                                    LKR {{ number_format((float) $invoice->balance_amount, 2) }}
                                                </div>
                                                <div class="text-danger fs-8">
                                                    Outstanding
                                                </div>
                                            @else
                                                <div class="fw-bolder text-success">
                                                    LKR 0.00
                                                </div>
                                                <div class="text-success fs-8">
                                                    Settled
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="text-end">
                                            <a href="{{ $showUrl }}" class="btn btn-sm btn-light-primary">
                                                <i class="bi bi-eye me-0.5"></i>
                                                View
                                            </a>
                                            <a href="#" class="btn btn-sm btn-light"
                                                data-receipt-template="invoice-receipt-template-{{ $invoice->getKey() }}"
                                                data-receipt-invoice="{{ $invoice->invoice_number }}">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @foreach ($invoices as $invoice)
                        <template id="invoice-receipt-template-{{ $invoice->getKey() }}">
                            @include('pages.apps.billing.invoices._receipt-card', ['invoice' => $invoice])
                        </template>
                    @endforeach

                    {{-- Pagination --}}
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mt-8 px-2">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-bold text-gray-800">
                                {{ $invoices->firstItem() ?? 0 }}
                            </span>
                            to
                            <span class="fw-bold text-gray-800">
                                {{ $invoices->lastItem() ?? 0 }}
                            </span>
                            of
                            <span class="fw-bold text-gray-800">
                                {{ number_format($invoices->total()) }}
                            </span>
                            invoices
                        </div>
                        <div>
                            {{ $invoices->withQueryString()->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-20 px-5">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-primary mx-auto mb-7"
                            style="width: 90px; height: 90px;">
                            <i class="bi bi-receipt-cutoff text-primary" style="font-size: 2.7rem;"></i>
                        </div>
                        <h2 class="fw-bolder text-gray-900 mb-3">
                            No invoices found
                        </h2>
                        @if (request()->hasAny(['search', 'tenant_id', 'branch_id', 'payment_status', 'date']))
                            <div class="text-muted fs-6 mb-7">
                                We couldn't find any invoices matching your current filters.
                            </div>
                            <a href="{{ route('billing.invoices.index') }}" class="btn btn-light-primary">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                Reset Filters
                            </a>
                        @else
                            <div class="text-muted fs-6 mx-auto" style="max-width: 500px;">
                                Completed appointment checkouts and walk-in sales
                                will automatically appear here.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="modal fade" id="invoiceReceiptModal" tabindex="-1" aria-hidden="true" data-receipt-modal>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h3 class="modal-title fw-bolder text-gray-900">
                                Receipt Preview
                            </h3>
                            <div class="text-muted fs-8" data-receipt-modal-subtitle>
                                Review receipt details before printing.
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
