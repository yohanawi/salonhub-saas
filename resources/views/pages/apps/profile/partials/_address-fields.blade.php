{{-- Address form fields; $formAddress is the address being edited, or null for a new one. --}}
@php
    $value = fn (string $field, $default = null) => $formAddress ? $formAddress->{$field} : old($field, $default);
@endphp

<div class="row g-4 mb-5">
    <div class="col-md-6">
        <label class="form-label">Label</label>
        <input type="text" name="label" class="form-control form-control-solid" maxlength="50"
            placeholder="e.g. Home, Salon office" value="{{ $value('label') }}">
    </div>
    <div class="col-md-6">
        <label class="required form-label">Type</label>
        <select name="type" class="form-select form-select-solid" required>
            @foreach (\App\Models\Address::TYPES as $typeValue => $typeLabel)
                <option value="{{ $typeValue }}" @selected((int) $value('type', \App\Models\Address::TYPE_BILLING) === $typeValue)>{{ $typeLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="required form-label">Address line 1</label>
        <input type="text" name="address_line_1" class="form-control form-control-solid" maxlength="255" required
            value="{{ $value('address_line_1') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Address line 2</label>
        <input type="text" name="address_line_2" class="form-control form-control-solid" maxlength="255"
            value="{{ $value('address_line_2') }}">
    </div>
    <div class="col-md-6">
        <label class="required form-label">City</label>
        <input type="text" name="city" class="form-control form-control-solid" maxlength="100" required
            value="{{ $value('city') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Postal code</label>
        <input type="text" name="postal_code" class="form-control form-control-solid" maxlength="20"
            value="{{ $value('postal_code') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Province / State</label>
        <input type="text" name="state" class="form-control form-control-solid" maxlength="100"
            value="{{ $value('state') }}">
    </div>
    <div class="col-md-6">
        <label class="required form-label">Country</label>
        <input type="text" name="country" class="form-control form-control-solid" maxlength="100" required
            value="{{ $value('country', 'Sri Lanka') }}">
    </div>
    <div class="col-12">
        <label class="form-check form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox" name="is_primary" value="1" @checked($value('is_primary'))>
            <span class="form-check-label">Use as my primary address</span>
        </label>
    </div>
</div>
