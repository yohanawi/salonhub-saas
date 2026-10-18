{{--
    Payment inputs shared by appointment checkout and quick sale. Expects $paymentMethods, $total,
    $allowSplitPayment, $cashShift and $requireShift, plus optional $posPin for the cashier PIN prompt. The page reports its estimated total through
    window.posPayments.setTotal(total); the first line follows it until the cashier edits an amount.
--}}
@php
    $paymentRows = old('payments', [['payment_method_id' => old('payment_method_id'), 'amount' => old('amount', number_format($total, 2, '.', ''))]]);
@endphp

@if ($requireShift && ! $cashShift)
    <div class="alert alert-warning border-0 d-flex align-items-start mb-5">
        <i class="bi bi-cash-stack fs-3 me-3"></i>
        <div class="fs-8">
            No cash register shift is open at this branch, so cash cannot be taken.
            <a href="{{ route('billing.cash-register.index') }}" class="fw-bold">Open a shift</a>
        </div>
    </div>
@endif

@error('payments')
    <div class="alert alert-danger border-0 fs-8 mb-5">{{ $message }}</div>
@enderror

<div id="payment-lines" data-gift-lookup="{{ auth()->user()?->tenant_id ? route('billing.gift-cards.lookup') : '' }}">
    @foreach ($paymentRows as $index => $row)
        @include('pages.apps.billing._payment-line', ['index' => $index, 'row' => $row])
    @endforeach
</div>

<template id="payment-line-template">
    @include('pages.apps.billing._payment-line', ['index' => '__INDEX__', 'row' => []])
</template>

@if ($allowSplitPayment)
    <div class="d-flex justify-content-between align-items-center mb-5">
        <button type="button" class="btn btn-sm btn-light-primary" id="add-payment-line">
            <i class="bi bi-plus-lg me-1"></i>Split Payment
        </button>
        <div class="fs-8 text-end">
            <div class="text-muted">Remaining</div>
            <div class="fw-bold" id="payment-remaining">LKR 0.00</div>
        </div>
    </div>
@endif

<div class="d-flex justify-content-between fs-7 mb-5" id="payment-change-wrap" style="display: none !important;">
    <span class="text-muted">Change to give</span>
    <span class="fw-bold text-success" id="payment-change">LKR 0.00</span>
</div>

<div class="mb-7">
    <label class="form-label fw-semibold">Payment Notes</label>
    <textarea name="payment_notes" class="form-control form-control-solid" rows="3">{{ old('payment_notes') }}</textarea>
</div>

@include('pages.apps.billing._cashier-pin', ['posPin' => $posPin ?? null])

<button type="submit" class="btn btn-primary btn-lg w-100">
    <i class="bi bi-check2-circle me-2"></i>{{ $submitLabel ?? 'Complete Sale' }}
</button>

@push('scripts')
    <script>
        window.posPayments = (function() {
            const container = document.getElementById('payment-lines');
            const template = document.getElementById('payment-line-template');
            const remainingEl = document.getElementById('payment-remaining');
            const changeWrap = document.getElementById('payment-change-wrap');
            const changeEl = document.getElementById('payment-change');
            let nextIndex = container.querySelectorAll('[data-payment-line]').length + 100;
            let total = 0;

            function money(value) {
                return 'LKR ' + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            function lines() {
                return Array.from(container.querySelectorAll('[data-payment-line]'));
            }

            function refreshLine(line) {
                const type = line.querySelector('.payment-line-method')?.selectedOptions[0]?.dataset.type;
                line.querySelector('.payment-line-cash').style.display = type === 'cash' ? '' : 'none';
                line.querySelector('.payment-line-gift').style.display = type === 'gift_card' ? '' : 'none';
                line.querySelector('.payment-line-reference').style.display = type === 'gift_card' ? 'none' : '';
                line.querySelector('.payment-line-remove').style.display = lines().length > 1 ? '' : 'none';

                // Nothing is due when package credits or a deposit cover the bill, so a payment is optional.
                const nothingDue = total <= 0.004 && lines().length === 1;
                line.querySelector('.payment-line-method').required = ! nothingDue;
                line.querySelector('.payment-line-amount').required = ! nothingDue;
            }

            // Shows the card balance as soon as the code is entered, before the sale is submitted.
            function lookupGiftCard(input) {
                const status = input.closest('[data-payment-line]').querySelector('.payment-line-gift-status');
                const url = container.dataset.giftLookup;
                const code = input.value.trim();

                if (! url || code === '') {
                    status.textContent = '';
                    return;
                }

                fetch(url + '?code=' + encodeURIComponent(code), {headers: {'Accept': 'application/json'}})
                    .then((response) => response.ok ? response.json() : Promise.reject())
                    .then(function(card) {
                        status.textContent = card.message;
                        status.className = 'fs-8 mt-1 payment-line-gift-status ' + (card.usable ? 'text-success' : 'text-danger');
                    })
                    .catch(function() {
                        status.textContent = '';
                    });
            }

            // The first line absorbs whatever the other lines do not cover, unless the cashier typed it.
            function refresh() {
                const all = lines();
                const first = all[0];
                const others = all.slice(1).reduce((sum, line) => sum + Number(line.querySelector('.payment-line-amount').value || 0), 0);
                const firstAmount = first?.querySelector('.payment-line-amount');

                if (firstAmount && ! firstAmount.dataset.userEdited) {
                    const value = Math.max(0, total - others);
                    firstAmount.value = value > 0 ? value.toFixed(2) : '';
                }

                const paid = all.reduce((sum, line) => sum + Number(line.querySelector('.payment-line-amount').value || 0), 0);
                const change = all.reduce(function(sum, line) {
                    const isCash = line.querySelector('.payment-line-method')?.selectedOptions[0]?.dataset.type === 'cash';
                    const received = Number(line.querySelector('.payment-line-received')?.value || 0);
                    const amount = Number(line.querySelector('.payment-line-amount').value || 0);
                    return sum + (isCash && received > amount ? received - amount : 0);
                }, 0);

                if (remainingEl) {
                    const remaining = total - paid;
                    remainingEl.textContent = money(Math.abs(remaining)) + (remaining < -0.004 ? ' over' : '');
                    remainingEl.className = 'fw-bold ' + (Math.abs(remaining) < 0.005 ? 'text-success' : 'text-danger');
                }

                changeWrap.style.setProperty('display', change > 0 ? 'flex' : 'none', 'important');
                changeEl.textContent = money(change);
                all.forEach(refreshLine);
            }

            container.addEventListener('change', function(event) {
                if (event.target.classList.contains('payment-line-gift-code')) {
                    lookupGiftCard(event.target);
                }
                refresh();
            });
            container.addEventListener('input', function(event) {
                if (event.target.classList.contains('payment-line-amount')) {
                    event.target.dataset.userEdited = '1';
                }
                refresh();
            });
            container.addEventListener('click', function(event) {
                if (event.target.closest('.payment-line-remove')) {
                    event.target.closest('[data-payment-line]').remove();
                    refresh();
                }
            });

            document.getElementById('add-payment-line')?.addEventListener('click', function() {
                container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                const firstAmount = lines()[0]?.querySelector('.payment-line-amount');

                // Splitting hands control of the first amount back to the running balance.
                if (firstAmount) {
                    delete firstAmount.dataset.userEdited;
                }

                refresh();
                lines().at(-1).querySelector('.payment-line-method')?.focus();
            });

            @if (old('payments') || old('amount'))
                lines().forEach((line) => line.querySelector('.payment-line-amount').dataset.userEdited = '1');
            @endif

            refresh();

            return {
                setTotal(value) {
                    total = Math.max(0, Number(value || 0));
                    refresh();
                },
            };
        })();
    </script>
@endpush
