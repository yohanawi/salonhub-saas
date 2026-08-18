@csrf
@if ($method ?? false)
    @method($method)
@endif

@if ($tenants->isNotEmpty())
    <div class="card border-0 shadow-sm mb-8">
        <div class="card-body">
            <label class="form-label required fw-semibold">Salon</label>
            <select name="tenant_id" class="form-select form-select-solid" required>
                <option value="">Select salon</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
@endif

<div class="row g-8">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Expense Details</h3></div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select form-select-solid" required>
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $expense->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Category</label>
                        <select name="category_id" class="form-select form-select-solid" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $expense->category_id) === (string) $category->id)>{{ $category->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select form-select-solid">
                            <option value="">No vendor</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $expense->vendor_id) === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Expense Date</label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d') ?: today()->toDateString()) }}" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference Number</label>
                        <input name="reference_number" value="{{ old('reference_number', $expense->reference_number) }}" class="form-control form-control-solid">
                    </div>
                    <div class="col-12">
                        <label class="form-label required fw-semibold">Description</label>
                        <textarea name="description" rows="4" class="form-control form-control-solid" required>{{ old('description', $expense->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control form-control-solid">{{ old('notes', $expense->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @if (! $expense->exists)
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Receipt</h3></div>
                <div class="card-body">
                    <input type="file" name="receipt" class="form-control form-control-solid" accept=".jpg,.jpeg,.png,.pdf,.webp">
                </div>
            </div>
        @endif
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Financial Details</h3></div>
            <div class="card-body">
                <div class="mb-5">
                    <label class="form-label required fw-semibold">Subtotal</label>
                    <input type="number" min="0.01" step="0.01" name="subtotal" id="expense_subtotal" value="{{ old('subtotal', $expense->subtotal ?: $expense->amount) }}" class="form-control form-control-solid" required>
                </div>
                <div class="mb-5">
                    <label class="form-label">Tax</label>
                    <input type="number" min="0" step="0.01" name="tax_amount" id="expense_tax" value="{{ old('tax_amount', $expense->tax_amount ?? 0) }}" class="form-control form-control-solid">
                </div>
                <div class="mb-5">
                    <label class="form-label">Discount</label>
                    <input type="number" min="0" step="0.01" name="discount_amount" id="expense_discount" value="{{ old('discount_amount', $expense->discount_amount ?? 0) }}" class="form-control form-control-solid">
                </div>
                <div class="separator separator-dashed my-5"></div>
                <div class="d-flex justify-content-between fs-4">
                    <span class="fw-bold text-gray-900">Total</span>
                    <span class="fw-bolder text-primary" id="expense_total">LKR 0.00</span>
                </div>
            </div>
        </div>

        @if (! $expense->exists)
            <div class="card border-0 shadow-sm mb-8">
                <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Initial Payment</h3></div>
                <div class="card-body">
                    <div class="mb-5">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method_id" class="form-select form-select-solid">
                            <option value="">No payment now</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">Paid Amount</label>
                        <input type="number" min="0" step="0.01" name="paid_amount" value="{{ old('paid_amount', 0) }}" class="form-control form-control-solid">
                    </div>
                    <div class="mb-5">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', today()->toDateString()) }}" class="form-control form-control-solid">
                    </div>
                    <div>
                        <label class="form-label">Payment Reference</label>
                        <input name="payment_reference_number" value="{{ old('payment_reference_number') }}" class="form-control form-control-solid">
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle me-2"></i>{{ $submitLabel }}</button>
                <a href="{{ route('expense-management.expenses.index') }}" class="btn btn-light w-100 mt-3">Cancel</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const subtotal = document.getElementById('expense_subtotal');
            const tax = document.getElementById('expense_tax');
            const discount = document.getElementById('expense_discount');
            const total = document.getElementById('expense_total');

            function refreshTotal() {
                const value = Math.max(0, Number(subtotal?.value || 0) + Number(tax?.value || 0) - Number(discount?.value || 0));
                if (total) {
                    total.textContent = 'LKR ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }

            subtotal?.addEventListener('input', refreshTotal);
            tax?.addEventListener('input', refreshTotal);
            discount?.addEventListener('input', refreshTotal);
            refreshTotal();
        });
    </script>
@endpush
