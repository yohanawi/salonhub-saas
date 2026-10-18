{{--
    Advance payments held against the appointment. Expects $deposits (from AppointmentController::show):
    list, held, suggested, required, canCreate, canManage, paymentMethods, posPin.
--}}
@php
    $statusColors = [
        'held' => 'warning',
        'applied' => 'success',
        'refunded' => 'info',
        'forfeited' => 'secondary',
    ];
    $canTakeDeposit = $deposits['canCreate']
        && ! in_array($appointment->status, [\App\Models\Appointment::STATUS_CANCELLED, \App\Models\Appointment::STATUS_NO_SHOW], true)
        && ! $appointment->invoice;
@endphp

<div class="card border-0 shadow-sm mb-8">
    <div class="card-header border-0 pt-8 pb-2">
        <div class="card-title">
            <div>
                <h3 class="fw-bolder text-gray-900 mb-1">
                    <i class="bi bi-piggy-bank text-primary me-2"></i>Deposit
                </h3>
                <div class="text-muted fs-8">
                    @if ($deposits['required'] && $deposits['suggested'] > 0)
                        This salon asks for a deposit of LKR {{ number_format($deposits['suggested'], 2) }}.
                    @else
                        Advance payment, deducted from the final invoice at checkout.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body pt-4">
        @if ($deposits['held'] > 0)
            <div class="d-flex justify-content-between align-items-center rounded bg-light-warning p-4 mb-5">
                <span class="fw-semibold text-gray-800">Held for this booking</span>
                <span class="fw-bolder text-gray-900">LKR {{ number_format($deposits['held'], 2) }}</span>
            </div>
        @endif

        @forelse ($deposits['list'] as $deposit)
            <div class="border border-dashed rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold text-gray-900">{{ $deposit->deposit_number }}</div>
                        <div class="text-muted fs-8">
                            {{ $deposit->paymentMethod?->name ?? 'Payment' }} &middot; {{ $deposit->received_at?->format('M d, Y h:i A') }}
                        </div>
                        @if ($deposit->appliedInvoice)
                            <div class="fs-8 mt-1">
                                Applied to <a href="{{ route('billing.invoices.show', $deposit->appliedInvoice) }}">{{ $deposit->appliedInvoice->invoice_number }}</a>
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="fw-bolder text-gray-900">LKR {{ number_format((float) $deposit->amount, 2) }}</div>
                        <span class="badge badge-light-{{ $statusColors[$deposit->status] ?? 'secondary' }}">{{ $deposit->status_label }}</span>
                    </div>
                </div>

                @if ($deposits['canManage'] && $deposit->status === \App\Models\AppointmentDeposit::STATUS_HELD && $deposit->remainingAmount() > 0)
                    {{-- One reason, two outcomes: give the money back, or keep it (e.g. after a no-show). --}}
                    <form method="POST" action="{{ route('billing.deposits.refund', $deposit) }}" class="mt-4">
                        @csrf
                        <input type="text" name="reason" required maxlength="1000" placeholder="Reason (required)"
                            class="form-control form-control-solid form-control-sm mb-2" aria-label="Reason">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-light-info flex-grow-1">
                                Refund LKR {{ number_format($deposit->remainingAmount(), 2) }}
                            </button>
                            <button type="submit" formaction="{{ route('billing.deposits.forfeit', $deposit) }}" class="btn btn-sm btn-light-secondary flex-grow-1">
                                Keep (Forfeit)
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-muted fs-8 mb-5">No deposit has been taken for this appointment.</div>
        @endforelse

        @if ($canTakeDeposit)
            <form method="POST" action="{{ route('billing.appointments.deposits.store', $appointment) }}" class="mt-5">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <input type="number" name="amount" min="1" step="0.01" required placeholder="Amount"
                            value="{{ old('amount', $deposits['suggested'] > 0 ? number_format($deposits['suggested'], 2, '.', '') : '') }}"
                            class="form-control form-control-solid @error('amount') is-invalid @enderror" aria-label="Deposit amount">
                    </div>
                    <div class="col-6">
                        <select name="payment_method_id" required class="form-select form-select-solid @error('payment_method_id') is-invalid @enderror" aria-label="Payment method">
                            <option value="">Method</option>
                            @foreach ($deposits['paymentMethods'] as $method)
                                <option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}" placeholder="Reference (card / bank)"
                    class="form-control form-control-solid mb-3 @error('transaction_reference') is-invalid @enderror">
                @foreach (['amount', 'payment_method_id', 'transaction_reference'] as $field)
                    @error($field)
                        <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                    @enderror
                @endforeach

                @include('pages.apps.billing._cashier-pin', ['posPin' => $deposits['posPin']])

                <button type="submit" class="btn btn-light-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i>Take Deposit
                </button>
            </form>
        @endif
    </div>
</div>
