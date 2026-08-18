<x-default-layout>
    @section('title')
        Checkout
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.checkout.appointments.create', $appointment) }}
    @endsection

    @php
        $subtotal = $appointment->appointmentServices->sum(fn($item) => (float) ($item->unit_price ?? $item->price));
        $lineDiscount = $appointment->appointmentServices->sum(fn($item) => (float) $item->discount_amount);
        $tax = old('tax_amount', 0);
        $invoiceDiscount = old('discount_amount', 0);
        $total = max(0, $subtotal - $lineDiscount - (float) $invoiceDiscount + (float) $tax);
    @endphp

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-8">
                <div class="fw-bold text-gray-900 mb-1">Checkout cannot continue</div>
                <div class="text-gray-700">{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-2">Checkout {{ $appointment->appointment_number }}</h1>
                        <div class="text-muted">
                            {{ $appointment->customer?->full_name }} · {{ $appointment->branch?->name }} ·
                            {{ $appointment->completed_at?->format('M d, Y h:i A') }}
                        </div>
                    </div>
                    <a href="{{ route('appointment-management.appointments.show', $appointment) }}" class="btn btn-light align-self-start">
                        <i class="bi bi-arrow-left me-2"></i>Back to Appointment
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('billing.checkout.appointments.store', $appointment) }}" data-service-subtotal="{{ $subtotal }}" data-service-discount="{{ $lineDiscount }}">
            @csrf
            <div class="row g-8">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div>
                                    <h2 class="fw-bold text-gray-900 mb-1">Invoice Items</h2>
                                    <div class="text-muted fs-7">Copied from appointment service snapshots.</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                            <th>Service</th>
                                            <th>Staff</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-700">
                                        @foreach ($appointment->appointmentServices as $item)
                                            @php
                                                $unit = (float) ($item->unit_price ?? $item->price);
                                                $discount = (float) $item->discount_amount;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-gray-900">{{ $item->service_name ?: $item->service?->name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->duration_minutes }} min</div>
                                                </td>
                                                <td>{{ $item->staff?->full_name ?? '-' }}</td>
                                                <td class="text-end">LKR {{ number_format($unit, 2) }}</td>
                                                <td class="text-end">LKR {{ number_format($discount, 2) }}</td>
                                                <td class="text-end fw-bold text-gray-900">LKR {{ number_format(max(0, $unit - $discount), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($products->isNotEmpty())
                                <div class="separator separator-dashed my-7"></div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="fw-bold text-gray-900 mb-1">Retail Products</h3>
                                        <div class="text-muted fs-7">Add sellable products to this checkout.</div>
                                    </div>
                                    <button type="button" class="btn btn-light-primary btn-sm" id="add-product-row">
                                        <i class="bi bi-plus-lg me-2"></i>Add Product
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle gy-3" id="checkout-products-table">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Product</th>
                                                <th class="w-125px">Qty</th>
                                                <th class="text-end w-150px">Line Total</th>
                                                <th class="w-60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <select name="product_items[0][product_id]" class="form-select form-select-solid checkout-product">
                                                        <option value="" data-price="0">Select product</option>
                                                        @foreach ($products as $product)
                                                            @php($stock = $product->inventories->first())
                                                            <option value="{{ $product->id }}" data-price="{{ (float) $product->selling_price }}">
                                                                {{ $product->name }} - LKR {{ number_format((float) $product->selling_price, 2) }} - Stock {{ $stock?->available_quantity ?? 0 }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="number" min="1" name="product_items[0][quantity]" value="1" class="form-control form-control-solid checkout-product-qty"></td>
                                                <td class="text-end fw-bold checkout-product-total">LKR 0.00</td>
                                                <td><button type="button" class="btn btn-sm btn-icon btn-light-danger remove-product-row"><i class="bi bi-trash"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="mt-6">
                                <label class="form-label fw-semibold">Invoice Notes</label>
                                <textarea name="notes" class="form-control form-control-solid" rows="4">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm position-sticky" style="top: 100px;">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <h2 class="fw-bold text-gray-900 mb-0">Payment</h2>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold">LKR {{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Service Discounts</span>
                                <span class="fw-semibold">LKR {{ number_format($lineDiscount, 2) }}</span>
                            </div>
                            @if ($promotions->isNotEmpty())
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Available Promotion</label>
                                    <select name="promotion_id" class="form-select form-select-solid">
                                        <option value="">Auto apply best promotion</option>
                                        @foreach ($promotions as $promotion)
                                            <option value="{{ $promotion->id }}" @selected((string) old('promotion_id') === (string) $promotion->id)>
                                                {{ $promotion->name }} - {{ $promotion->discount_label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-5">
                                <label class="form-label fw-semibold">Coupon Code</label>
                                <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" class="form-control form-control-solid text-uppercase">
                            </div>
                            <div class="mb-5">
                                <label class="form-label fw-semibold">Invoice Discount</label>
                                <input type="number" name="discount_amount" id="discount_amount" min="0" step="0.01" value="{{ old('discount_amount', 0) }}" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label fw-semibold">Tax Amount</label>
                                <input type="number" name="tax_amount" id="tax_amount" min="0" step="0.01" value="{{ old('tax_amount', 0) }}" class="form-control form-control-solid">
                            </div>
                            @if ($loyaltyAccount || $activeMembership)
                                <div class="rounded border p-4 mb-5">
                                    @if ($activeMembership)
                                        <div class="fw-bold text-gray-900 mb-1">{{ $activeMembership->plan?->name }} Member</div>
                                        <div class="text-muted fs-8 mb-3">Expires {{ $activeMembership->end_date?->format('M d, Y') }}</div>
                                    @endif
                                    @if ($loyaltyAccount)
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">Available Points</span>
                                            <span class="fw-bold">{{ number_format($loyaltyAccount->available_points) }}</span>
                                        </div>
                                        <label class="form-label fw-semibold">Redeem Points</label>
                                        <input type="number" name="loyalty_points_to_redeem" id="loyalty_points_to_redeem" min="0" value="{{ old('loyalty_points_to_redeem', 0) }}" class="form-control form-control-solid">
                                    @endif
                                </div>
                            @endif
                            <div class="separator separator-dashed my-5"></div>
                            <div class="d-flex justify-content-between fs-4 mb-7">
                                <span class="fw-bold text-gray-900">Estimated Total</span>
                                <span class="fw-bolder text-primary" id="estimated_total">LKR {{ number_format($total, 2) }}</span>
                            </div>

                            <div class="mb-5">
                                <label class="form-label required fw-semibold">Payment Method</label>
                                <select name="payment_method_id" id="payment_method_id" class="form-select form-select-solid" required>
                                    <option value="">Select method</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->id }}" data-type="{{ $method->type }}" data-reference="{{ $method->requires_reference ? '1' : '0' }}" @selected((string) old('payment_method_id') === (string) $method->id)>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-5">
                                <label class="form-label required fw-semibold">Payment Amount</label>
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">LKR</span>
                                    <input type="number" name="amount" id="payment_amount" min="0.01" step="0.01" value="{{ old('amount', number_format($total, 2, '.', '')) }}" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-5" id="cash_received_wrap">
                                <label class="form-label fw-semibold">Cash Received</label>
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">LKR</span>
                                    <input type="number" name="cash_received" min="0" step="0.01" value="{{ old('cash_received') }}" class="form-control">
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">Transaction Reference</label>
                                <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}" class="form-control form-control-solid">
                            </div>

                            <div class="mb-7">
                                <label class="form-label fw-semibold">Payment Notes</label>
                                <textarea name="payment_notes" class="form-control form-control-solid" rows="3">{{ old('payment_notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-check2-circle me-2"></i>Complete Sale
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const method = document.getElementById('payment_method_id');
                const cashWrap = document.getElementById('cash_received_wrap');
                const form = document.querySelector('form[data-service-subtotal]');
                const productsTable = document.getElementById('checkout-products-table');
                const addProductRow = document.getElementById('add-product-row');
                const discountInput = document.getElementById('discount_amount');
                const taxInput = document.getElementById('tax_amount');
                const estimatedTotal = document.getElementById('estimated_total');
                const paymentAmount = document.getElementById('payment_amount');
                let productIndex = 1;

                function refreshCash() {
                    const selected = method?.selectedOptions[0];
                    cashWrap.style.display = selected?.dataset.type === 'cash' ? '' : 'none';
                }

                method?.addEventListener('change', refreshCash);
                refreshCash();

                function money(value) {
                    return 'LKR ' + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                function recalculate() {
                    const serviceSubtotal = Number(form?.dataset.serviceSubtotal || 0);
                    const serviceDiscount = Number(form?.dataset.serviceDiscount || 0);
                    let productSubtotal = 0;

                    productsTable?.querySelectorAll('tbody tr').forEach(function(row) {
                        const selected = row.querySelector('.checkout-product')?.selectedOptions[0];
                        const qty = Number(row.querySelector('.checkout-product-qty')?.value || 0);
                        const lineTotal = Number(selected?.dataset.price || 0) * qty;
                        productSubtotal += lineTotal;
                        const totalCell = row.querySelector('.checkout-product-total');
                        if (totalCell) {
                            totalCell.textContent = money(lineTotal);
                        }
                    });

                    const discount = Number(discountInput?.value || 0);
                    const tax = Number(taxInput?.value || 0);
                    const total = Math.max(0, serviceSubtotal + productSubtotal - serviceDiscount - discount + tax);

                    if (estimatedTotal) {
                        estimatedTotal.textContent = money(total);
                    }

                    if (paymentAmount && ! paymentAmount.dataset.userEdited) {
                        paymentAmount.value = total.toFixed(2);
                    }
                }

                addProductRow?.addEventListener('click', function() {
                    const body = productsTable.querySelector('tbody');
                    const clone = body.querySelector('tr').cloneNode(true);

                    clone.querySelectorAll('select, input').forEach(function(field) {
                        field.name = field.name.replace(/product_items\[\d+\]/, 'product_items[' + productIndex + ']');
                        field.value = field.tagName === 'INPUT' ? '1' : '';
                    });

                    clone.querySelector('.checkout-product-total').textContent = money(0);
                    body.appendChild(clone);
                    productIndex++;
                    recalculate();
                });

                productsTable?.addEventListener('input', recalculate);
                productsTable?.addEventListener('change', recalculate);
                productsTable?.addEventListener('click', function(event) {
                    if (! event.target.closest('.remove-product-row')) {
                        return;
                    }

                    const rows = productsTable.querySelectorAll('tbody tr');
                    if (rows.length > 1) {
                        event.target.closest('tr').remove();
                        recalculate();
                    }
                });

                paymentAmount?.addEventListener('input', function() {
                    paymentAmount.dataset.userEdited = '1';
                });
                discountInput?.addEventListener('input', recalculate);
                taxInput?.addEventListener('input', recalculate);
                recalculate();
            });
        </script>
    @endpush
</x-default-layout>
