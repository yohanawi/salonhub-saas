{{-- One tender line. $index is numeric for re-rendered lines and "__INDEX__" inside the JS template. --}}
<div class="rounded border border-dashed p-4 mb-4" data-payment-line>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="form-label required fw-semibold mb-0">Payment Method</label>
        <button type="button" class="btn btn-sm btn-icon btn-light-danger payment-line-remove" aria-label="Remove payment" style="display: none;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <select name="payments[{{ $index }}][payment_method_id]" class="form-select form-select-solid mb-3 payment-line-method @error("payments.{$index}.payment_method_id") is-invalid @enderror" required>
        <option value="">Select method</option>
        @foreach ($paymentMethods as $method)
            <option value="{{ $method->id }}" data-type="{{ $method->type }}" data-reference="{{ $method->requires_reference ? '1' : '0' }}" @selected((string) ($row['payment_method_id'] ?? '') === (string) $method->id)>
                {{ $method->name }}
            </option>
        @endforeach
    </select>
    @error("payments.{$index}.payment_method_id")
        <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
    @enderror

    <div class="input-group input-group-solid mb-3">
        <span class="input-group-text">LKR</span>
        <input type="number" name="payments[{{ $index }}][amount]" min="0.01" step="0.01" value="{{ $row['amount'] ?? '' }}" placeholder="Amount"
            class="form-control payment-line-amount @error("payments.{$index}.amount") is-invalid @enderror" required>
    </div>
    @error("payments.{$index}.amount")
        <div class="text-danger fs-8 mb-3">{{ $message }}</div>
    @enderror

    <div class="payment-line-cash mb-3">
        <div class="input-group input-group-solid">
            <span class="input-group-text">Cash Received</span>
            <input type="number" name="payments[{{ $index }}][cash_received]" min="0" step="0.01" value="{{ $row['cash_received'] ?? '' }}"
                class="form-control payment-line-received @error("payments.{$index}.cash_received") is-invalid @enderror">
        </div>
        @error("payments.{$index}.cash_received")
            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <input type="text" name="payments[{{ $index }}][transaction_reference]" value="{{ $row['transaction_reference'] ?? '' }}" placeholder="Transaction reference"
        class="form-control form-control-solid @error("payments.{$index}.transaction_reference") is-invalid @enderror">
    @error("payments.{$index}.transaction_reference")
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
