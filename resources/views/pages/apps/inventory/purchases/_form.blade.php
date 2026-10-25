@include('pages/apps.inventory.partials._alerts')

@if ($isSuperAdmin && ! $selectedTenant)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <label class="form-label required">Salon</label>
            <select class="form-select form-select-solid w-300px"
                onchange="if (this.value) window.location='{{ route('inventory.purchases.create') }}?tenant_id=' + this.value">
                <option value="">Select salon to continue</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
@else
    @php
        $currency = $selectedTenant?->currency ?: 'LKR';
        $rows = collect($itemRows)->values();
    @endphp

    <div class="row g-6">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold text-gray-900 mb-0">Products</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" id="po_add_row" class="btn btn-sm btn-light-primary" @disabled($products->isEmpty())>
                            <i class="bi bi-plus-lg me-1"></i>Add Product
                        </button>
                    </div>
                </div>
                <div class="card-body pt-2">
                    @error('items')
                        <div class="alert alert-danger py-3 fs-7">{{ $message }}</div>
                    @enderror

                    <div class="table-responsive">
                        <table class="table align-middle gy-3">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-250px">Product</th>
                                    <th class="w-125px">Quantity</th>
                                    <th class="w-150px">Unit Cost</th>
                                    <th class="w-125px text-end">Line Total</th>
                                    <th class="w-50px"></th>
                                </tr>
                            </thead>
                            <tbody id="po_rows">
                                @foreach ($rows as $index => $row)
                                    @include('pages/apps.inventory.purchases._row', ['index' => $index, 'row' => $row])
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="po_empty" class="text-muted fs-7 py-4 {{ $rows->isNotEmpty() ? 'd-none' : '' }}">
                        No products added yet.
                    </div>

                    <template id="po_row_template">
                        @include('pages/apps.inventory.purchases._row', ['index' => '__INDEX__', 'row' => []])
                    </template>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-6">
                    <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Order Details</h3></div>
                </div>
                <div class="card-body pt-2">
                    @if ($isSuperAdmin)
                        <input type="hidden" name="tenant_id" value="{{ $selectedTenant->id }}">
                    @endif

                    <div class="mb-5">
                        <label class="form-label required">Deliver To (Branch)</label>
                        <select name="branch_id" class="form-select form-select-solid" required>
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $purchase->branch_id ?? request('branch_id')) === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select form-select-solid">
                            <option value="">No supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $purchase->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @can('create', \App\Models\Supplier::class)
                            <a href="{{ route('inventory.suppliers.create') }}" class="fs-8">+ Add a supplier</a>
                        @endcan
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-6">
                            <label class="form-label required">Order Date</label>
                            <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($purchase->purchase_date)->toDateString()) }}" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Expected Delivery</label>
                            <input type="date" name="expected_date" value="{{ old('expected_date', optional($purchase->expected_date)->toDateString()) }}" class="form-control form-control-solid">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Supplier Invoice / Quote No.</label>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number', $purchase->invoice_number) }}" class="form-control form-control-solid">
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-6">
                            <label class="form-label">Discount ({{ $currency }})</label>
                            <input type="number" step="0.01" min="0" name="discount" id="po_discount" value="{{ old('discount', $purchase->discount ?? 0) }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tax ({{ $currency }})</label>
                            <input type="number" step="0.01" min="0" name="tax" id="po_tax" value="{{ old('tax', $purchase->tax ?? 0) }}" class="form-control form-control-solid">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control form-control-solid">{{ old('notes', $purchase->notes) }}</textarea>
                    </div>

                    <div class="rounded-3 bg-light p-5">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span id="po_subtotal" class="fw-semibold">0.00</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Discount</span><span id="po_discount_view" class="fw-semibold">0.00</span></div>
                        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Tax</span><span id="po_tax_view" class="fw-semibold">0.00</span></div>
                        <div class="d-flex justify-content-between border-top pt-3"><span class="fw-bold">Total ({{ $currency }})</span><span id="po_total" class="fw-bolder fs-4 text-primary">0.00</span></div>
                    </div>
                </div>
                <div class="card-footer d-grid gap-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>{{ $purchase->exists ? 'Save Changes' : 'Save as Draft' }}</button>
                    <a href="{{ $purchase->exists ? route('inventory.purchases.show', $purchase) : route('inventory.purchases.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.getElementById('po_rows');
                const template = document.getElementById('po_row_template');
                const empty = document.getElementById('po_empty');
                let nextIndex = rows.querySelectorAll('tr').length;

                const money = (value) => Number(value || 0).toFixed(2);

                function recalc() {
                    let subtotal = 0;

                    rows.querySelectorAll('tr').forEach(function(row) {
                        const qty = parseFloat(row.querySelector('.po-qty').value) || 0;
                        const cost = parseFloat(row.querySelector('.po-cost').value) || 0;
                        const line = qty * cost;
                        row.querySelector('.po-line-total').textContent = money(line);
                        subtotal += line;
                    });

                    const discount = Math.min(subtotal, parseFloat(document.getElementById('po_discount').value) || 0);
                    const tax = parseFloat(document.getElementById('po_tax').value) || 0;

                    document.getElementById('po_subtotal').textContent = money(subtotal);
                    document.getElementById('po_discount_view').textContent = money(discount);
                    document.getElementById('po_tax_view').textContent = money(tax);
                    document.getElementById('po_total').textContent = money(subtotal - discount + tax);
                    empty.classList.toggle('d-none', rows.querySelectorAll('tr').length > 0);
                }

                document.getElementById('po_add_row')?.addEventListener('click', function() {
                    rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                    recalc();
                });

                rows.addEventListener('click', function(event) {
                    const button = event.target.closest('.po-remove');

                    if (button) {
                        button.closest('tr').remove();
                        recalc();
                    }
                });

                rows.addEventListener('change', function(event) {
                    // Picking a product fills in its last known cost price.
                    if (event.target.classList.contains('po-product')) {
                        const option = event.target.selectedOptions[0];
                        const costInput = event.target.closest('tr').querySelector('.po-cost');

                        if (option && option.dataset.cost && !parseFloat(costInput.value)) {
                            costInput.value = option.dataset.cost;
                        }
                    }

                    recalc();
                });

                rows.addEventListener('input', recalc);
                document.getElementById('po_discount').addEventListener('input', recalc);
                document.getElementById('po_tax').addEventListener('input', recalc);

                if (rows.querySelectorAll('tr').length === 0) {
                    document.getElementById('po_add_row')?.click();
                }

                recalc();
            });
        </script>
    @endpush
@endif
