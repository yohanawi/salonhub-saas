<x-default-layout>
    @section('title')
        Sell Gift Card
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.gift-cards.create') }}
    @endsection

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-8">
                <div class="fw-bold text-gray-900 mb-2">Gift card cannot be issued</div>
                <ul class="text-gray-700 mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('billing.gift-cards.store') }}">
            @csrf
            <div class="row g-8">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div>
                                    <h2 class="fw-bold text-gray-900 mb-1">Gift Card</h2>
                                    <div class="text-muted fs-7">Sold at {{ $branch->name }}. The value is spent later as a payment method.</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold">Value (LKR)</label>
                                    <input type="number" name="amount" id="gift_card_amount" min="1" step="0.01" value="{{ old('amount') }}" required
                                        class="form-control form-control-solid @error('amount') is-invalid @enderror">
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        @foreach ([2500, 5000, 10000, 20000] as $preset)
                                            <button type="button" class="btn btn-sm btn-light" data-gift-preset="{{ $preset }}">{{ number_format($preset) }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Card Code</label>
                                    <input type="text" name="code" value="{{ old('code') }}" maxlength="30" placeholder="Leave blank to generate"
                                        class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror">
                                    <div class="text-muted fs-8 mt-2">Enter the number printed on a physical card, or let the system create one.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Expires On</label>
                                    <input type="date" name="expires_at" value="{{ old('expires_at', now()->addYear()->toDateString()) }}"
                                        class="form-control form-control-solid @error('expires_at') is-invalid @enderror">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Bought By (Customer)</label>
                                    <select name="customer_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Optional">
                                        <option value="">Optional</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                                {{ $customer->full_name }}@if ($customer->phone) - {{ $customer->phone }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Recipient Name</label>
                                    <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" maxlength="150" class="form-control form-control-solid">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Recipient Phone</label>
                                    <input type="text" name="recipient_phone" value="{{ old('recipient_phone') }}" maxlength="30" class="form-control form-control-solid">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Message</label>
                                    <textarea name="message" rows="2" maxlength="500" class="form-control form-control-solid" placeholder="Happy Avurudu!">{{ old('message') }}</textarea>
                                </div>
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
                            <div class="d-flex justify-content-between fs-4 mb-7">
                                <span class="fw-bold text-gray-900">Total</span>
                                <span class="fw-bolder text-primary" id="gift_card_total">LKR 0.00</span>
                            </div>
                            @include('pages.apps.billing._payment-fields', ['total' => (float) old('amount', 0), 'submitLabel' => 'Issue Gift Card'])
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const amount = document.getElementById('gift_card_amount');

                function refresh() {
                    const value = Math.max(0, Number(amount.value || 0));
                    document.getElementById('gift_card_total').textContent = 'LKR ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    window.posPayments?.setTotal(value);
                }

                document.querySelectorAll('[data-gift-preset]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        amount.value = button.dataset.giftPreset;
                        refresh();
                    });
                });

                amount.addEventListener('input', refresh);
                refresh();
            });
        </script>
    @endpush
</x-default-layout>
