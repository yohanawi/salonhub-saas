{{-- Refund history and the refund / return form for the invoice show page. --}}
@if ($invoice->refunds->isNotEmpty())
    <div class="card border-0 shadow-sm mb-8">
        <div class="card-header border-0 pt-8">
            <div class="card-title">
                <div>
                    <h3 class="fw-bold text-gray-900 mb-1">Refunds</h3>
                    <div class="text-muted fs-8">Money returned to the customer for this invoice.</div>
                </div>
            </div>
        </div>
        <div class="card-body pt-2">
            @foreach ($invoice->refunds->sortByDesc('id') as $refund)
                @php
                    $refundClass = match ($refund->status) {
                        \App\Models\Refund::STATUS_COMPLETED => 'info',
                        \App\Models\Refund::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    };
                @endphp
                <div class="py-5 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-4">
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-gray-900 fs-6">{{ $refund->refund_number }}</span>
                                <span class="badge badge-light-{{ $refundClass }}">{{ $refund->status_label }}</span>
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                {{ $refund->paymentMethod?->name ?? 'Refund' }}
                                @if ($refund->transaction_reference)
                                    <span class="mx-2">•</span>Ref: <span class="fw-semibold text-gray-700">{{ $refund->transaction_reference }}</span>
                                @endif
                                <span class="mx-2">•</span>Requested by {{ $refund->requestedBy?->name ?? 'Unknown' }} {{ $refund->created_at?->format('M d, Y · h:i A') }}
                                @if ($refund->processed_at && $refund->processedBy)
                                    <span class="mx-2">•</span>{{ $refund->status === \App\Models\Refund::STATUS_REJECTED ? 'Rejected' : 'Approved' }} by {{ $refund->processedBy->name }}
                                @endif
                            </div>
                            <div class="text-gray-700 fs-7 mt-2">{{ $refund->reason }}</div>
                            @if ($refund->items->isNotEmpty())
                                <div class="text-muted fs-8 mt-2">
                                    Returned:
                                    {{ $refund->items->map(fn ($item) => $item->quantity . ' × ' . ($item->invoiceItem?->item_name ?? 'Item') . ($item->restock ? ' (restocked)' : ''))->implode(', ') }}
                                </div>
                            @endif
                            @if ($refund->rejection_reason)
                                <div class="text-danger fs-8 mt-2">Rejected: {{ $refund->rejection_reason }}</div>
                            @endif
                        </div>
                        <div class="text-md-end">
                            <div class="text-muted fs-8 mb-1">Amount</div>
                            <div class="fw-bolder text-info fs-5">LKR {{ number_format((float) $refund->amount, 2) }}</div>
                        </div>
                    </div>

                    @if ($refund->status === \App\Models\Refund::STATUS_PENDING && $canApproveRefund)
                        <div class="d-flex flex-column flex-md-row gap-3 mt-4">
                            <form method="POST" action="{{ route('billing.invoices.refunds.approve', [$invoice, $refund]) }}"
                                data-swal-confirm
                                data-swal-title="Approve this refund?"
                                data-swal-text="LKR {{ number_format((float) $refund->amount, 2) }} will be recorded as refunded, and stock, commission and loyalty points will be adjusted."
                                data-swal-icon="question"
                                data-swal-confirm-button="Approve refund"
                                data-swal-cancel-button="Cancel">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light-success">
                                    <i class="bi bi-check2 me-1"></i>Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('billing.invoices.refunds.reject', [$invoice, $refund]) }}" class="d-flex gap-3 flex-grow-1">
                                @csrf
                                <input type="text" name="rejection_reason" class="form-control form-control-sm form-control-solid" placeholder="Reason for rejecting" required>
                                <button type="submit" class="btn btn-sm btn-light-danger text-nowrap">
                                    <i class="bi bi-x me-1"></i>Reject
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($canRequestRefund)
    <div class="card border-0 shadow-sm mb-8">
        <div class="card-header border-0 pt-8">
            <div class="card-title">
                <div>
                    <h3 class="fw-bold text-gray-900 mb-1">Refund / Return</h3>
                    <div class="text-muted fs-8">
                        Up to LKR {{ number_format($refundableAmount, 2) }} can be refunded.
                        @unless ($canApproveRefund)
                            Your request will need approval before money is returned.
                        @endunless
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-4">
            <form method="POST" action="{{ route('billing.invoices.refunds.store', $invoice) }}" id="refund-form" data-refundable="{{ $refundableAmount }}"
                data-swal-confirm
                data-swal-title="Refund this invoice?"
                data-swal-text="The refund amount will be recorded as paid back to the customer."
                data-swal-icon="warning"
                data-swal-confirm-button="Yes, refund"
                data-swal-cancel-button="Cancel">
                @csrf

                @if ($returnableItems->isNotEmpty())
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-semibold text-gray-800">Items being returned <span class="text-muted fs-8">(optional for a money-only refund)</span></div>
                        <button type="button" class="btn btn-sm btn-light-primary" id="refund-select-all">Return everything</button>
                    </div>
                    <div class="table-responsive mb-6">
                        <table class="table align-middle table-row-dashed gy-3">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th>Item</th>
                                    <th class="text-end">Unit Value</th>
                                    <th class="text-center">Returnable</th>
                                    <th class="w-100px">Return Qty</th>
                                    <th class="text-center">Restock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($returnableItems as $line)
                                    @php($item = $line['item'])
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $item->item_name ?: $item->description }}</div>
                                            <div class="text-muted fs-8">
                                                {{ ucfirst($item->item_type) }} · Sold {{ $item->quantity }}@if ($line['returned'] > 0) · Already returned {{ $line['returned'] }}@endif
                                            </div>
                                        </td>
                                        <td class="text-end">LKR {{ number_format($line['unit_value'], 2) }}</td>
                                        <td class="text-center">{{ $line['remaining'] }}</td>
                                        <td>
                                            <input type="number" min="0" max="{{ $line['remaining'] }}" name="items[{{ $item->id }}][quantity]"
                                                value="{{ old("items.{$item->id}.quantity", 0) }}" data-unit-value="{{ $line['unit_value'] }}"
                                                class="form-control form-control-sm form-control-solid refund-qty @error("items.{$item->id}.quantity") is-invalid @enderror"
                                                @disabled($line['remaining'] === 0)>
                                            @error("items.{$item->id}.quantity")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-center">
                                            @if ($line['restockable'])
                                                <input type="hidden" name="items[{{ $item->id }}][restock]" value="0">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="items[{{ $item->id }}][restock]" value="1" @checked(old("items.{$item->id}.restock", true))>
                                                </div>
                                            @else
                                                <span class="text-muted fs-8">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Refund Amount</label>
                        <div class="input-group input-group-solid">
                            <span class="input-group-text">LKR</span>
                            <input type="number" name="amount" id="refund_amount" min="0.01" max="{{ number_format($refundableAmount, 2, '.', '') }}" step="0.01"
                                value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        </div>
                        @error('amount')
                            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Refund Method</label>
                        <select name="payment_method_id" class="form-select form-select-solid @error('payment_method_id') is-invalid @enderror" required>
                            <option value="">Select method</option>
                            @foreach ($refundMethods as $method)
                                <option value="{{ $method->id }}" @selected((string) old('payment_method_id', $invoice->payments->first()?->payment_method_id) === (string) $method->id)>
                                    {{ $method->name }}{{ $method->requires_reference ? ' (reference required)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Transaction Reference</label>
                        <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}"
                            class="form-control form-control-solid @error('transaction_reference') is-invalid @enderror">
                        @error('transaction_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Reason</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" required placeholder="Why is this being refunded?"
                            class="form-control form-control-solid @error('reason') is-invalid @enderror">
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button class="btn btn-light-info w-100 mt-6" type="submit">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                    {{ $canApproveRefund ? 'Refund Now' : 'Submit Refund Request' }}
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('refund-form');
                const amount = document.getElementById('refund_amount');
                const refundable = Number(form.dataset.refundable || 0);
                const quantities = form.querySelectorAll('.refund-qty');

                // Suggests the value of the returned items; the cashier can still type a different amount.
                function suggest() {
                    if (amount.dataset.userEdited) {
                        return;
                    }

                    const total = Array.from(quantities).reduce((sum, input) => sum + Number(input.value || 0) * Number(input.dataset.unitValue || 0), 0);
                    amount.value = total > 0 ? Math.min(total, refundable).toFixed(2) : '';
                }

                quantities.forEach((input) => input.addEventListener('input', suggest));
                amount.addEventListener('input', () => amount.dataset.userEdited = '1');

                document.getElementById('refund-select-all')?.addEventListener('click', function() {
                    quantities.forEach((input) => {
                        if (! input.disabled) {
                            input.value = input.max;
                        }
                    });
                    delete amount.dataset.userEdited;
                    suggest();
                });
            });
        </script>
    @endpush
@endif
