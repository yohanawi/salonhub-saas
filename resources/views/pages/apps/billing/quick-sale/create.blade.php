<x-default-layout>
    @section('title')
        Quick Sale
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.quick-sale.create') }}
    @endsection

    @php
        $customerModes = array_filter([
            'existing' => 'Existing Customer',
            'new' => $canCreateCustomer ? 'New Customer' : null,
            'walk_in' => $allowWalkIns ? 'Walk-in (No Customer)' : null,
        ]);
        $customerMode = old('customer_mode', $customers->isEmpty() && $allowWalkIns ? 'walk_in' : 'existing');
        $serviceRows = old('service_items', $services->isNotEmpty() ? [['quantity' => 1]] : []);
        $productRows = old('product_items', $services->isEmpty() && $products->isNotEmpty() ? [['quantity' => 1]] : []);
        $taxLabel = $taxConfig['enabled']
            ? $taxConfig['name'] . ' (' . rtrim(rtrim(number_format($taxConfig['rate'], 2), '0'), '.') . '%' . ($taxConfig['inclusive'] ? ', included' : '') . ')'
            : 'Tax';
        $total = 0;
    @endphp

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-8">
                <div class="fw-bold text-gray-900 mb-2">Sale cannot be completed</div>
                <ul class="text-gray-700 mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-2">Quick Sale</h1>
                        <div class="text-muted">
                            Walk-in services and retail sales at <span class="fw-semibold text-gray-800">{{ $branch->name }}</span> without an appointment.
                        </div>
                    </div>
                    <a href="{{ route('billing.invoices.index') }}" class="btn btn-light align-self-start">
                        <i class="bi bi-arrow-left me-2"></i>Back to Invoices
                    </a>
                </div>
            </div>
        </div>

        @include('pages.apps.billing.quick-sale._offline-panel')

        <form method="POST" action="{{ route('billing.quick-sale.store') }}" id="quick-sale-form" data-tax-rate="{{ $taxConfig['rate'] }}" data-tax-inclusive="{{ $taxConfig['inclusive'] ? '1' : '0' }}">
            @csrf
            {{-- One id per sale, so a retried or offline-synced submit cannot create a second invoice. --}}
            <input type="hidden" name="client_uuid" id="client_uuid" value="{{ old('client_uuid') }}">
            <input type="hidden" name="_pos_user" value="{{ auth()->id() }}">
            <input type="hidden" name="offline_estimated_total" id="offline_estimated_total" value="0">
            <div class="row g-8">
                <div class="col-xl-8">
                    {{-- Customer --}}
                    <div class="card border-0 shadow-sm mb-8">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div>
                                    <h2 class="fw-bold text-gray-900 mb-1">Customer</h2>
                                    <div class="text-muted fs-7">Registered customers earn loyalty points and membership benefits.</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="d-flex flex-wrap gap-3 mb-6">
                                @foreach ($customerModes as $mode => $label)
                                    <input type="radio" class="btn-check" name="customer_mode" id="customer_mode_{{ $mode }}" value="{{ $mode }}" @checked($customerMode === $mode)>
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary px-6" for="customer_mode_{{ $mode }}">{{ $label }}</label>
                                @endforeach
                            </div>

                            <div data-customer-panel="existing">
                                <label class="form-label fw-semibold">Customer</label>
                                <select name="customer_id" id="quick_sale_customer" class="form-select form-select-solid @error('customer_id') is-invalid @enderror" data-control="select2" data-width="100%" data-placeholder="Search customer by name or phone">
                                    <option value="">Search customer by name or phone</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" data-points="{{ (int) ($loyaltyPoints[$customer->id] ?? 0) }}" data-package-credits="{{ json_encode($packageCredits[$customer->id] ?? (object) []) }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                            {{ $customer->full_name }}@if ($customer->phone) - {{ $customer->phone }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <div class="rounded bg-light-success p-4 mt-4" id="package_credit_wrap" style="display: none;">
                                    <input type="hidden" name="use_package_credits" value="0">
                                    <label class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="use_package_credits" value="1" id="use_package_credits" @checked(old('use_package_credits', '1') === '1')>
                                        <span class="form-check-label fw-semibold text-gray-800">Use package credits</span>
                                    </label>
                                    <div class="text-muted fs-8 mt-2" id="package_credit_summary"></div>
                                </div>
                            </div>

                            @if ($canCreateCustomer)
                                <div data-customer-panel="new" class="row g-5">
                                    <div class="col-md-6">
                                        <label class="form-label required fw-semibold">First Name</label>
                                        <input type="text" name="new_customer[first_name]" value="{{ old('new_customer.first_name') }}" class="form-control form-control-solid @error('new_customer.first_name') is-invalid @enderror">
                                        @error('new_customer.first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Last Name</label>
                                        <input type="text" name="new_customer[last_name]" value="{{ old('new_customer.last_name') }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required fw-semibold">Phone</label>
                                        <input type="text" name="new_customer[phone]" value="{{ old('new_customer.phone') }}" class="form-control form-control-solid @error('new_customer.phone') is-invalid @enderror">
                                        @error('new_customer.phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" name="new_customer[email]" value="{{ old('new_customer.email') }}" class="form-control form-control-solid @error('new_customer.email') is-invalid @enderror">
                                        @error('new_customer.email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            @if ($allowWalkIns)
                                <div data-customer-panel="walk_in" class="text-muted">
                                    The invoice will be issued to "Walk-in Customer". Loyalty points and membership benefits do not apply.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-8">
                            @if ($services->isEmpty() && $products->isEmpty())
                                <div class="text-center text-muted py-10">
                                    No services or sellable products are available at {{ $branch->name }}.
                                </div>
                            @endif

                            @if ($services->isNotEmpty())
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="fw-bold text-gray-900 mb-1">Services</h3>
                                        <div class="text-muted fs-7">Services performed for this walk-in. Staff earn commission on their lines.</div>
                                    </div>
                                    <button type="button" class="btn btn-light-primary btn-sm" data-add-row="service">
                                        <i class="bi bi-plus-lg me-2"></i>Add Service
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle gy-3" id="quick-sale-services-table">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th class="min-w-200px">Service</th>
                                                <th class="min-w-175px">Performed By</th>
                                                <th class="w-100px">Qty</th>
                                                <th class="text-end w-150px">Line Total</th>
                                                <th class="w-60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($serviceRows as $index => $row)
                                                @include('pages.apps.billing.quick-sale._service-row', ['index' => $index, 'row' => $row])
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <template id="quick-sale-service-template">
                                    @include('pages.apps.billing.quick-sale._service-row', ['index' => '__INDEX__', 'row' => ['quantity' => 1]])
                                </template>
                            @endif

                            @if ($products->isNotEmpty())
                                @if ($services->isNotEmpty())
                                    <div class="separator separator-dashed my-7"></div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="fw-bold text-gray-900 mb-1">Retail Products</h3>
                                        <div class="text-muted fs-7">Stock is deducted from {{ $branch->name }} when the sale completes.</div>
                                    </div>
                                    <button type="button" class="btn btn-light-primary btn-sm" data-add-row="product">
                                        <i class="bi bi-plus-lg me-2"></i>Add Product
                                    </button>
                                </div>
                                <div class="mb-4">
                                    <div class="input-group input-group-solid">
                                        <span class="input-group-text"><i class="bi bi-upc-scan fs-4"></i></span>
                                        <input type="text" id="quick_sale_scan" class="form-control form-control-solid" autocomplete="off"
                                            placeholder="Scan barcode or type SKU, then press Enter" aria-label="Scan barcode">
                                    </div>
                                    <div class="fs-8 mt-2" id="quick_sale_scan_feedback" aria-live="polite"></div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle gy-3" id="quick-sale-products-table">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th class="min-w-200px">Product</th>
                                                <th class="min-w-175px">Sold By</th>
                                                <th class="w-100px">Qty</th>
                                                <th class="text-end w-150px">Line Total</th>
                                                <th class="w-60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($productRows as $index => $row)
                                                @include('pages.apps.billing.quick-sale._product-row', ['index' => $index, 'row' => $row])
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <template id="quick-sale-product-template">
                                    @include('pages.apps.billing.quick-sale._product-row', ['index' => '__INDEX__', 'row' => ['quantity' => 1]])
                                </template>
                            @endif

                            <div class="mt-6">
                                <label class="form-label fw-semibold">Invoice Notes</label>
                                <textarea name="notes" class="form-control form-control-solid" rows="3">{{ old('notes') }}</textarea>
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
                            <div class="d-flex justify-content-between mb-5">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold" id="estimated_subtotal">LKR 0.00</span>
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
                                <span class="fw-semibold" id="estimated_tax">LKR 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-5" id="package_discount_row" style="display: none !important;">
                                <span class="text-muted">Package Credits</span>
                                <span class="fw-semibold text-success" id="estimated_package">- LKR 0.00</span>
                            </div>
                            <div class="rounded border p-4 mb-5" id="loyalty_wrap" style="display: none;">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Available Points</span>
                                    <span class="fw-bold" id="loyalty_available">0</span>
                                </div>
                                <label class="form-label fw-semibold">Redeem Points</label>
                                <input type="number" name="loyalty_points_to_redeem" id="loyalty_points_to_redeem" min="0" value="{{ old('loyalty_points_to_redeem', 0) }}" class="form-control form-control-solid @error('loyalty_points_to_redeem') is-invalid @enderror">
                                @error('loyalty_points_to_redeem')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @include('pages.apps.billing._tips')
                            <div class="separator separator-dashed my-5"></div>
                            <div class="d-flex justify-content-between fs-4 mb-2">
                                <span class="fw-bold text-gray-900">Estimated Total</span>
                                <span class="fw-bolder text-primary" id="estimated_total">LKR 0.00</span>
                            </div>
                            <div class="text-muted fs-8 mb-7">Membership, promotion and loyalty discounts are applied when the sale completes.</div>

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
                const form = document.getElementById('quick-sale-form');
                const servicesBody = document.querySelector('#quick-sale-services-table tbody');
                const productsBody = document.querySelector('#quick-sale-products-table tbody');
                const discountInput = document.getElementById('discount_amount');
                const customerSelect = document.getElementById('quick_sale_customer');
                const loyaltyWrap = document.getElementById('loyalty_wrap');
                const loyaltyInput = document.getElementById('loyalty_points_to_redeem');
                const defaultTaxRate = Number(form.dataset.taxRate || 0);
                const taxInclusive = form.dataset.taxInclusive === '1';
                const counters = {
                    service: servicesBody ? servicesBody.querySelectorAll('tr').length + 100 : 0,
                    product: productsBody ? productsBody.querySelectorAll('tr').length + 100 : 0,
                };

                function money(value) {
                    return 'LKR ' + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                function taxFor(amount, rate) {
                    if (rate <= 0 || amount <= 0) {
                        return 0;
                    }

                    return taxInclusive ? amount - (amount / (1 + rate / 100)) : amount * rate / 100;
                }

                function selectedOption(row, selector) {
                    return row.querySelector(selector)?.selectedOptions[0];
                }

                // Only staff assigned to the chosen service can be picked for that line.
                function filterStaff(row) {
                    const serviceId = row.querySelector('.quick-sale-service')?.value;
                    const staffSelect = row.querySelector('.quick-sale-service-staff');
                    let eligible = [];

                    staffSelect.querySelectorAll('option[data-services]').forEach(function(option) {
                        const allowed = serviceId !== '' && option.dataset.services.split(',').includes(serviceId);
                        option.hidden = ! allowed;
                        option.disabled = ! allowed;

                        if (allowed) {
                            eligible.push(option);
                        }
                    });

                    if (staffSelect.selectedOptions[0]?.disabled) {
                        staffSelect.value = '';
                    }

                    if (staffSelect.value === '' && eligible.length === 1) {
                        staffSelect.value = eligible[0].value;
                    }
                }

                function servicePrice(row) {
                    const service = selectedOption(row, '.quick-sale-service');
                    const staff = selectedOption(row, '.quick-sale-service-staff');
                    const customPrices = staff?.dataset.prices ? JSON.parse(staff.dataset.prices) : {};

                    return Number(customPrices[service?.value] ?? service?.dataset.price ?? 0);
                }

                // Remaining package credits of the selected customer, per service id.
                function packageCredits() {
                    if (customerMode() !== 'existing' || ! document.getElementById('use_package_credits')?.checked) {
                        return {};
                    }

                    try {
                        return JSON.parse(customerSelect?.selectedOptions[0]?.dataset.packageCredits || '{}');
                    } catch (error) {
                        return {};
                    }
                }

                // Mirrors TaxService: the invoice discount is shared across lines before tax is applied.
                // Package credits come off first, as CheckoutService applies them before other discounts.
                function recalculate() {
                    const lines = [];
                    const credits = packageCredits();
                    let packageDiscount = 0;

                    servicesBody?.querySelectorAll('tr').forEach(function(row) {
                        const qty = Math.max(0, Number(row.querySelector('.quick-sale-qty')?.value || 0));
                        const price = servicePrice(row);
                        const lineTotal = price * qty;
                        const serviceId = row.querySelector('.quick-sale-service')?.value;
                        const covered = Math.min(qty, Number(credits[serviceId] || 0));

                        if (covered > 0) {
                            credits[serviceId] -= covered;
                            packageDiscount += price * covered;
                        }

                        row.querySelector('.quick-sale-line-total').textContent = money(lineTotal);
                        lines.push({base: lineTotal - price * covered, rate: defaultTaxRate});
                    });

                    productsBody?.querySelectorAll('tr').forEach(function(row) {
                        const product = selectedOption(row, '.quick-sale-product');
                        const qty = Math.max(0, Number(row.querySelector('.quick-sale-qty')?.value || 0));
                        const lineTotal = Number(product?.dataset.price || 0) * qty;
                        const productRate = Number(product?.dataset.taxRate || 0);
                        row.querySelector('.quick-sale-line-total').textContent = money(lineTotal);
                        lines.push({base: lineTotal, rate: productRate > 0 ? productRate : defaultTaxRate});
                    });

                    const subtotal = lines.reduce((sum, line) => sum + line.base, 0) + packageDiscount;
                    const payable = subtotal - packageDiscount;
                    const discount = Math.min(Math.max(0, Number(discountInput?.value || 0)), payable);
                    const tax = lines.reduce(function(sum, line) {
                        const share = payable > 0 ? discount * line.base / payable : 0;
                        return sum + Math.round(taxFor(line.base - share, line.rate) * 100) / 100;
                    }, 0);
                    const total = Math.max(0, payable - discount + (taxInclusive ? 0 : tax));
                    const tips = window.posTips?.total() || 0;

                    document.getElementById('estimated_subtotal').textContent = money(subtotal);
                    document.getElementById('estimated_package').textContent = '- ' + money(packageDiscount);
                    document.getElementById('package_discount_row').style.setProperty('display', packageDiscount > 0 ? 'flex' : 'none', 'important');
                    document.getElementById('estimated_tax').textContent = money(tax);
                    document.getElementById('estimated_total').textContent = money(total + tips);
                    document.getElementById('offline_estimated_total').value = (total + tips).toFixed(2);

                    window.posPayments?.setTotal(total + tips);
                }

                function addRow(type) {
                    const template = document.getElementById('quick-sale-' + type + '-template');
                    const body = type === 'service' ? servicesBody : productsBody;

                    body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', counters[type]++));

                    if (type === 'service') {
                        filterStaff(body.lastElementChild);
                    }

                    recalculate();
                }

                function customerMode() {
                    return form.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';
                }

                function refreshCustomer() {
                    const mode = customerMode();

                    form.querySelectorAll('[data-customer-panel]').forEach(function(panel) {
                        panel.style.display = panel.dataset.customerPanel === mode ? '' : 'none';
                    });

                    const points = mode === 'existing' ? Number(customerSelect?.selectedOptions[0]?.dataset.points || 0) : 0;
                    loyaltyWrap.style.display = points > 0 ? '' : 'none';
                    document.getElementById('loyalty_available').textContent = points.toLocaleString();
                    loyaltyInput.max = points;

                    if (points <= 0) {
                        loyaltyInput.value = 0;
                    }

                    let credits = {};

                    try {
                        credits = mode === 'existing' ? JSON.parse(customerSelect?.selectedOptions[0]?.dataset.packageCredits || '{}') : {};
                    } catch (error) {
                        credits = {};
                    }

                    const creditNames = Object.entries(credits).map(function([serviceId, remaining]) {
                        const option = document.querySelector('#quick-sale-service-template')?.content.querySelector('.quick-sale-service option[value="' + serviceId + '"]');
                        return remaining + ' x ' + (option ? option.textContent.trim().split(' - ')[0] : 'service');
                    });

                    document.getElementById('package_credit_wrap').style.display = creditNames.length ? '' : 'none';
                    document.getElementById('package_credit_summary').textContent = creditNames.length ? 'Remaining: ' + creditNames.join(', ') : '';
                    recalculate();
                }

                document.querySelectorAll('[data-add-row]').forEach(function(button) {
                    button.addEventListener('click', () => addRow(button.dataset.addRow));
                });

                [servicesBody, productsBody].forEach(function(body) {
                    body?.addEventListener('click', function(event) {
                        if (event.target.closest('.quick-sale-remove-row')) {
                            event.target.closest('tr').remove();
                            recalculate();
                        }
                    });
                    body?.addEventListener('change', function(event) {
                        if (event.target.classList.contains('quick-sale-service')) {
                            filterStaff(event.target.closest('tr'));
                        }
                        recalculate();
                    });
                    body?.addEventListener('input', recalculate);
                });

                form.querySelectorAll('input[name="customer_mode"]').forEach((radio) => radio.addEventListener('change', refreshCustomer));

                // select2 emits jQuery change events, which native listeners do not receive.
                if (window.jQuery && customerSelect) {
                    window.jQuery(customerSelect).on('change', refreshCustomer);
                } else {
                    customerSelect?.addEventListener('change', refreshCustomer);
                }

                discountInput?.addEventListener('input', recalculate);
                document.getElementById('use_package_credits')?.addEventListener('change', recalculate);
                document.addEventListener('pos:tips-changed', recalculate);

                // Barcode scanners type the code and press Enter. Codes match a product's barcode or SKU.
                const scanInput = document.getElementById('quick_sale_scan');
                const scanFeedback = document.getElementById('quick_sale_scan_feedback');
                const productOptions = Array.from(document.querySelectorAll('#quick-sale-product-template')).flatMap(
                    (template) => Array.from(template.content.querySelectorAll('.quick-sale-product option[value]:not([value=""])'))
                );

                function scanMessage(text, ok) {
                    scanFeedback.textContent = text;
                    scanFeedback.className = 'fs-8 mt-2 ' + (ok ? 'text-success' : 'text-danger');
                }

                function addScannedProduct(code) {
                    const needle = code.trim().toLowerCase();

                    if (needle === '' || ! productsBody) {
                        return;
                    }

                    const match = productOptions.find((option) => (option.dataset.barcode || '').toLowerCase() === needle)
                        || productOptions.find((option) => (option.dataset.sku || '').toLowerCase() === needle);

                    if (! match) {
                        scanMessage('No sellable product found for "' + code.trim() + '".', false);
                        return;
                    }

                    const rows = Array.from(productsBody.querySelectorAll('tr'));
                    const existing = rows.find((row) => row.querySelector('.quick-sale-product')?.value === match.value);

                    if (existing) {
                        const qty = existing.querySelector('.quick-sale-qty');
                        qty.value = Math.max(0, Number(qty.value || 0)) + 1;
                    } else {
                        let row = rows.find((row) => row.querySelector('.quick-sale-product')?.value === '');

                        if (! row) {
                            addRow('product');
                            row = productsBody.lastElementChild;
                        }

                        row.querySelector('.quick-sale-product').value = match.value;
                        row.querySelector('.quick-sale-qty').value = 1;
                    }

                    recalculate();
                    scanMessage('Added ' + match.textContent.trim().split(' - ')[0] + '.', true);
                }

                scanInput?.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        // Enter must not submit the sale form.
                        event.preventDefault();
                        addScannedProduct(scanInput.value);
                        scanInput.value = '';
                    }
                });

                // A scan while nothing is focused (e.g. after clicking the page) still reaches the cart.
                let scanBuffer = '';
                let lastKeyAt = 0;

                document.addEventListener('keydown', function(event) {
                    const target = event.target;

                    if (! scanInput || target.closest?.('input, textarea, select, [contenteditable="true"], .select2-container') || event.ctrlKey || event.altKey || event.metaKey) {
                        return;
                    }

                    const now = Date.now();

                    // Scanners send keys a few milliseconds apart; a person typing is much slower.
                    if (now - lastKeyAt > 60) {
                        scanBuffer = '';
                    }

                    lastKeyAt = now;

                    if (event.key === 'Enter') {
                        if (scanBuffer.length >= 3) {
                            event.preventDefault();
                            addScannedProduct(scanBuffer);
                        }

                        scanBuffer = '';
                    } else if (event.key.length === 1) {
                        scanBuffer += event.key;
                    }
                });

                servicesBody?.querySelectorAll('tr').forEach(filterStaff);
                refreshCustomer();
                recalculate();

                const clientUuid = document.getElementById('client_uuid');

                if (! clientUuid.value) {
                    clientUuid.value = window.crypto?.randomUUID ? crypto.randomUUID()
                        : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => ((c === 'x' ? Math.random() * 16 : (Math.random() * 4 + 8)) | 0).toString(16));
                }

                // Offline, only cash and card can be taken: gift card balances and PINs need the server.
                form.addEventListener('submit', function(event) {
                    if (navigator.onLine) {
                        return;
                    }

                    const giftCard = Array.from(form.querySelectorAll('.payment-line-method')).some((select) => select.selectedOptions[0]?.dataset.type === 'gift_card');
                    const packageCredits = document.getElementById('use_package_credits')?.checked && document.getElementById('package_credit_wrap')?.style.display !== 'none';

                    if (giftCard || packageCredits) {
                        event.preventDefault();
                        alert('You are offline. Gift cards and package credits need the internet; take cash or card, or wait until you are back online.');
                    }
                });
            });
        </script>
    @endpush
</x-default-layout>
