<x-default-layout>
    @section('title')
        Invoices
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.invoices.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-8">{{ session('status') }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-1">Invoices</h1>
                        <div class="text-muted">Track checkout invoices, payment status and balances.</div>
                    </div>
                    <span class="badge badge-light-primary align-self-start px-4 py-3">{{ number_format($invoices->total()) }} invoices</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body">
                <form method="GET" action="{{ route('billing.invoices.index') }}">
                    <div class="row g-5">
                        <div class="col-xl-3">
                            <label class="form-label fw-semibold">Search</label>
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Invoice, appointment or customer">
                        </div>
                        @if (($isSuperAdmin ?? false) && $tenants->isNotEmpty())
                            <div class="col-xl-2">
                                <label class="form-label fw-semibold">Salon</label>
                                <select name="tenant_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">All salons</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-xl-2">
                            <label class="form-label fw-semibold">Branch</label>
                            <select name="branch_id" class="form-select form-select-solid" data-control="select2">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2">
                            <label class="form-label fw-semibold">Payment</label>
                            <select name="payment_status" class="form-select form-select-solid">
                                <option value="">All</option>
                                @foreach ($paymentStatuses as $status)
                                    <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-xl-1 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body pt-6">
                @if ($invoices->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Invoice</th>
                                    @if ($isSuperAdmin ?? false)
                                        <th>Salon</th>
                                    @endif
                                    <th>Customer</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @foreach ($invoices as $invoice)
                                    @php
                                        $paymentClass = match ($invoice->payment_status) {
                                            'paid' => 'success',
                                            'partially_paid' => 'warning',
                                            'refunded' => 'info',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('billing.invoices.show', $invoice) }}" class="fw-bold text-gray-900 text-hover-primary">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                            <div class="text-muted fs-8">{{ $invoice->issued_at?->format('M d, Y h:i A') }}</div>
                                        </td>
                                        @if ($isSuperAdmin ?? false)
                                            <td>{{ $invoice->tenant?->name ?? '-' }}</td>
                                        @endif
                                        <td>{{ $invoice->customer?->full_name ?? 'Walk-in' }}</td>
                                        <td><span class="badge badge-light-info">{{ $invoice->branch?->name ?? '-' }}</span></td>
                                        <td><span class="badge badge-light-{{ $paymentClass }}">{{ $invoice->payment_status_label }}</span></td>
                                        <td class="text-end">LKR {{ number_format((float) $invoice->total, 2) }}</td>
                                        <td class="text-end">LKR {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                        <td class="text-end fw-bold">LKR {{ number_format((float) $invoice->balance_amount, 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('billing.invoices.show', $invoice) }}" class="btn btn-sm btn-light-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8">{{ $invoices->links() }}</div>
                @else
                    <div class="text-center py-15">
                        <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                        <h3 class="fw-bold text-gray-900 mt-5">No invoices found</h3>
                        <div class="text-muted">Completed appointment checkouts will appear here.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
