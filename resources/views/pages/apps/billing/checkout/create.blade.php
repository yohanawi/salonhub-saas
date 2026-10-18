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
        $invoiceDiscount = $canApplyDiscount ? (float) old('discount_amount', 0) : 0;
        $taxBase = max(0, $subtotal - $lineDiscount - $invoiceDiscount);
        $tax = match (true) {
            ! $taxConfig['enabled'] => 0,
            $taxConfig['inclusive'] => round($taxBase - $taxBase / (1 + $taxConfig['rate'] / 100), 2),
            default => round($taxBase * $taxConfig['rate'] / 100, 2),
        };
        $total = $taxBase + ($taxConfig['inclusive'] ? 0 : $tax);
        $taxLabel = $taxConfig['enabled']
            ? $taxConfig['name'] . ' (' . rtrim(rtrim(number_format($taxConfig['rate'], 2), '0'), '.') . '%' . ($taxConfig['inclusive'] ? ', included' : '') . ')'
            : 'Tax';
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

        <form method="POST" action="{{ route('billing.checkout.appointments.store', $appointment) }}" data-service-subtotal="{{ $subtotal }}" data-service-discount="{{ $lineDiscount }}" data-tax-rate="{{ $taxConfig['rate'] }}" data-tax-inclusive="{{ $taxConfig['inclusive'] ? '1' : '0' }}">
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
                                                            <option value="{{ $product->id }}" data-price="{{ (float) $product->selling_price }}" data-tax-rate="{{ (float) $product->tax_rate }}">
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
                            @if ($canApplyDiscount)
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Invoice Discount</label>
                                    <input type="number" name="discount_amount" id="discount_amount" min="0" step="0.01" value="{{ old('discount_amount', 0) }}" class="form-control form-control-solid @error('discount_amount') is-invalid @enderror">
                                    @error('discount_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                            <div class="d-flex justify-content-between mb-5">
                                <span class="text-muted">{{ $taxLabel }}</span>
                                <span class="fw-semibold" id="estimated_tax">LKR {{ number_format($tax, 2) }}</span>
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

                            @include('pages.apps.billing._payment-fields')
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form[data-service-subtotal]');
                const productsTable = document.getElementById('checkout-products-table');
                const addProductRow = document.getElementById('add-product-row');
                const discountInput = document.getElementById('discount_amount');
                const estimatedTax = document.getElementById('estimated_tax');
                const estimatedTotal = document.getElementById('estimated_total');
                const defaultTaxRate = Number(form?.dataset.taxRate || 0);
                const taxInclusive = form?.dataset.taxInclusive === '1';
                let productIndex = 1;

                function money(value) {
                    return 'LKR ' + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                function taxFor(amount, rate) {
                    if (rate <= 0 || amount <= 0) {
                        return 0;
                    }

                    return taxInclusive ? amount - (amount / (1 + rate / 100)) : amount * rate / 100;
                }

                // Mirrors TaxService: the invoice discount is shared across lines before tax is applied.
                // Membership and promotion discounts are only known after submit, so this stays an estimate.
                function recalculate() {
                    const serviceSubtotal = Number(form?.dataset.serviceSubtotal || 0);
                    const serviceDiscount = Number(form?.dataset.serviceDiscount || 0);
                    const lines = [{base: Math.max(0, serviceSubtotal - serviceDiscount), rate: defaultTaxRate}];

                    productsTable?.querySelectorAll('tbody tr').forEach(function(row) {
                        const selected = row.querySelector('.checkout-product')?.selectedOptions[0];
                        const qty = Number(row.querySelector('.checkout-product-qty')?.value || 0);
                        const lineTotal = Number(selected?.dataset.price || 0) * qty;
                        const productRate = Number(selected?.dataset.taxRate || 0);
                        lines.push({base: lineTotal, rate: productRate > 0 ? productRate : defaultTaxRate});
                        const totalCell = row.querySelector('.checkout-product-total');
                        if (totalCell) {
                            totalCell.textContent = money(lineTotal);
                        }
                    });

                    const baseTotal = lines.reduce((sum, line) => sum + line.base, 0);
                    const discount = Math.min(Math.max(0, Number(discountInput?.value || 0)), baseTotal);
                    const tax = lines.reduce(function(sum, line) {
                        const share = baseTotal > 0 ? discount * line.base / baseTotal : 0;
                        return sum + Math.round(taxFor(line.base - share, line.rate) * 100) / 100;
                    }, 0);
                    const total = Math.max(0, baseTotal - discount + (taxInclusive ? 0 : tax));

                    if (estimatedTax) {
                        estimatedTax.textContent = money(tax);
                    }

                    if (estimatedTotal) {
                        estimatedTotal.textContent = money(total);
                    }

                    window.posPayments?.setTotal(total);
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

                discountInput?.addEventListener('input', recalculate);
                recalculate();
            });
        </script>
    @endpush
</x-default-layout>
