{{--
    Cashier confirmation shared by the POS payment forms. Expects $posPin from PosPinService::viewData();
    renders nothing when the salon does not require a staff PIN.
--}}
@if (! empty($posPin['required']))
    <div class="rounded border border-dashed border-primary p-5 mb-5">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-shield-lock text-primary fs-3 me-3"></i>
            <div>
                <div class="fw-bold text-gray-900">Cashier PIN</div>
                <div class="text-muted fs-8">Confirm who is taking this payment.</div>
            </div>
        </div>

        @if ($posPin['cashiers']->isEmpty())
            <div class="alert alert-warning border-0 fs-8 mb-0">
                No staff at this branch have a POS PIN yet. Set one on the staff profile before taking payments.
            </div>
        @else
            <div class="row g-3">
                <div class="col-7">
                    <select name="cashier_staff_id" class="form-select form-select-solid @error('cashier_pin') is-invalid @enderror" aria-label="Cashier">
                        <option value="">Select cashier</option>
                        @foreach ($posPin['cashiers'] as $cashier)
                            <option value="{{ $cashier->id }}" @selected((string) old('cashier_staff_id', $posPin['defaultCashierId']) === (string) $cashier->id)>{{ $cashier->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-5">
                    <input type="password" name="cashier_pin" inputmode="numeric" autocomplete="off" maxlength="12" placeholder="PIN"
                        class="form-control form-control-solid @error('cashier_pin') is-invalid @enderror" aria-label="Cashier PIN">
                </div>
            </div>
        @endif

        @error('cashier_pin')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
@endif
