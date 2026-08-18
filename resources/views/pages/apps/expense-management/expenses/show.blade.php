<x-default-layout>
    @section('title') Expense @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.expenses.show', $expense) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <div class="d-flex gap-3 align-items-center flex-wrap">
                            <h1 class="fw-bolder text-gray-900 mb-0">{{ $expense->expense_number }}</h1>
                            <span class="badge badge-light-{{ $expense->approval_status === 'approved' ? 'success' : ($expense->approval_status === 'rejected' ? 'danger' : 'warning') }}">{{ $expense->approval_status_label }}</span>
                            <span class="badge badge-light-{{ $expense->payment_status === 'paid' ? 'success' : 'warning' }}">{{ $expense->payment_status_label }}</span>
                            <span class="badge badge-light">{{ $expense->expense_status_label }}</span>
                        </div>
                        <div class="text-muted mt-2">{{ $expense->branch?->name }} - {{ $expense->category?->full_name }} - {{ $expense->expense_date?->format('M d, Y') }}</div>
                    </div>
                    <div class="d-flex gap-3 flex-wrap align-self-start">
                        @can('update', $expense)
                            <a href="{{ route('expense-management.expenses.edit', $expense) }}" class="btn btn-light-primary"><i class="bi bi-pencil-square me-2"></i>Edit</a>
                        @endcan
                        <a href="{{ route('expense-management.expenses.index') }}" class="btn btn-light">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Expense Details</h3></div>
                    <div class="card-body">
                        <div class="row g-6">
                            @foreach ([
                                'Branch' => $expense->branch?->name,
                                'Category' => $expense->category?->full_name,
                                'Vendor' => $expense->vendor?->name ?? '-',
                                'Reference' => $expense->reference_number ?? '-',
                                'Created By' => $expense->creator?->name ?? '-',
                                'Approved By' => $expense->approvedBy?->name ?? '-',
                            ] as $label => $value)
                                <div class="col-md-6">
                                    <div class="text-muted fs-8">{{ $label }}</div>
                                    <div class="fw-semibold text-gray-900">{{ $value }}</div>
                                </div>
                            @endforeach
                            <div class="col-12">
                                <div class="text-muted fs-8">Description</div>
                                <div class="fw-semibold text-gray-900">{{ $expense->description }}</div>
                            </div>
                            @if ($expense->notes)
                                <div class="col-12">
                                    <div class="text-muted fs-8">Notes</div>
                                    <div class="fw-semibold text-gray-900">{{ $expense->notes }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Payments</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($expense->payments as $payment)
                            <div class="d-flex justify-content-between border-bottom py-4">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $payment->paymentMethod?->name ?? str($payment->payment_method)->headline() }}</div>
                                    <div class="text-muted fs-8">{{ $payment->payment_date?->format('M d, Y') }} @if($payment->reference_number)- Ref: {{ $payment->reference_number }}@endif</div>
                                </div>
                                <div class="fw-bold text-gray-900">LKR {{ number_format((float) $payment->amount, 2) }}</div>
                            </div>
                        @empty
                            <div class="text-muted py-6">No payments recorded.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Attachments</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($expense->attachments as $attachment)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <div class="fw-bold text-gray-900">{{ $attachment->file_name }}</div>
                                    <div class="text-muted fs-8">{{ $attachment->file_type }} - {{ number_format(($attachment->file_size ?? 0) / 1024, 1) }} KB</div>
                                </div>
                                <a class="btn btn-sm btn-light-primary" target="_blank" href="{{ Storage::disk('public')->url($attachment->file_path) }}">Open</a>
                            </div>
                        @empty
                            <div class="text-muted py-6">No receipts uploaded.</div>
                        @endforelse

                        @can('update', $expense)
                            <form method="POST" action="{{ route('expense-management.expenses.attachments.store', $expense) }}" enctype="multipart/form-data" class="mt-6">
                                @csrf
                                <div class="input-group">
                                    <input type="file" name="receipt" class="form-control" required>
                                    <button class="btn btn-light-primary">Upload</button>
                                </div>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Financial Summary</h3></div>
                    <div class="card-body">
                        @foreach ([
                            'Subtotal' => $expense->subtotal,
                            'Tax' => $expense->tax_amount,
                            'Discount' => $expense->discount_amount,
                            'Total' => $expense->total_amount,
                            'Paid' => $expense->paid_amount,
                            'Balance' => $expense->balance_amount,
                        ] as $label => $amount)
                            <div class="d-flex justify-content-between mb-3 {{ in_array($label, ['Total', 'Balance'], true) ? 'fw-bold text-gray-900' : 'text-muted' }}">
                                <span>{{ $label }}</span>
                                <span>LKR {{ number_format((float) $amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @can('pay', $expense)
                    <div class="card border-0 shadow-sm mb-8">
                        <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Add Payment</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('expense-management.expenses.payments.store', $expense) }}">
                                @csrf
                                <div class="mb-5">
                                    <label class="form-label required">Payment Method</label>
                                    <select name="payment_method_id" class="form-select form-select-solid" required>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label required">Amount</label>
                                    <input type="number" min="0.01" max="{{ $expense->balance_amount }}" step="0.01" name="amount" value="{{ number_format((float) $expense->balance_amount, 2, '.', '') }}" class="form-control form-control-solid" required>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label required">Payment Date</label>
                                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" class="form-control form-control-solid" required>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">Reference</label>
                                    <input name="reference_number" class="form-control form-control-solid">
                                </div>
                                <button class="btn btn-primary w-100">Record Payment</button>
                            </form>
                        </div>
                    </div>
                @endcan

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Actions</h3></div>
                    <div class="card-body d-grid gap-3">
                        @can('approve', $expense)
                            <form method="POST" action="{{ route('expense-management.expenses.approve', $expense) }}">@csrf<button class="btn btn-light-success w-100">Approve</button></form>
                            <form method="POST" action="{{ route('expense-management.expenses.reject', $expense) }}">@csrf<button class="btn btn-light-danger w-100">Reject</button></form>
                        @endcan
                        @can('cancel', $expense)
                            <form method="POST" action="{{ route('expense-management.expenses.cancel', $expense) }}">
                                @csrf
                                <textarea name="cancel_reason" rows="3" class="form-control form-control-solid mb-3" required placeholder="Cancel reason"></textarea>
                                <button class="btn btn-light-danger w-100">Cancel Expense</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
