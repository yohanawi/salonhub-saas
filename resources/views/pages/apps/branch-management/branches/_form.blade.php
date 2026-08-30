@php
    $isEdit = $branch->exists;
@endphp


<div class="row g-8">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-building text-primary fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            General Information
                        </h2>

                        <div class="text-muted fs-8">
                            Basic details used to identify this salon branch.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="row g-6">
                    {{-- Salon / Tenant --}}
                    @if (!$isEdit && auth()->user()?->hasRole('Super Admin'))
                        <div class="col-12">
                            <div
                                class="rounded-4 bg-light-primary p-5 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-white">
                                            <i class="bi bi-shop text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-900 mb-1">
                                            Choose Salon / Tenant
                                        </div>
                                        <div class="text-muted fs-8">
                                            This branch will be created under the selected salon.
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <select name="tenant_id"
                                        class="form-select bg-white @error('tenant_id') is-invalid @enderror w-400px" required
                                        data-control="select2">
                                        <option value="">
                                            Select salon
                                        </option>
                                        @foreach ($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                                {{ $tenant->name }}
                                                {{ $tenant->email ? ' - ' . $tenant->email : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tenant_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Branch Name --}}
                    <div class="col-md-8">
                        <label class="form-label required fw-semibold">
                            Branch Name
                        </label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-shop text-muted"></i>
                            </span>
                            <input type="text" name="name" value="{{ old('name', $branch->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="e.g. Colombo Branch" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Branch Code --}}
                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Branch Code
                        </label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-upc-scan text-muted"></i>
                            </span>
                            <input type="text" name="code" value="{{ old('code', $branch->code) }}"
                                class="form-control text-uppercase @error('code') is-invalid @enderror" maxlength="20"
                                placeholder="CMB" required>
                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="text-muted fs-8 mt-2">
                            Short unique code for this salon.
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Phone Number
                        </label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-telephone text-muted"></i>
                            </span>
                            <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}"
                                class="form-control @error('phone') is-invalid @enderror" placeholder="+94 11 234 5678">
                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input type="email" name="email" value="{{ old('email', $branch->email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="branch@example.com">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-geo-alt text-success fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Location
                        </h2>
                        <div class="text-muted fs-8">
                            Physical address and location details for this branch.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="row g-6">
                    {{-- Address 1 --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Address Line 1
                        </label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-geo text-muted"></i>
                            </span>
                            <input type="text" name="address_line_1"
                                value="{{ old('address_line_1', $branch->address_line_1 ?? $branch->address) }}"
                                class="form-control @error('address_line_1') is-invalid @enderror"
                                placeholder="Street address">
                            @error('address_line_1')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Address 2 --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Address Line 2
                        </label>

                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-signpost text-muted"></i>
                            </span>
                            <input type="text" name="address_line_2"
                                value="{{ old('address_line_2', $branch->address_line_2) }}"
                                class="form-control @error('address_line_2') is-invalid @enderror"
                                placeholder="Apartment, floor, landmark...">
                            @error('address_line_2')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    {{-- City --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            City
                        </label>
                        <input type="text" name="city" value="{{ old('city', $branch->city) }}"
                            class="form-control @error('city') is-invalid @enderror" placeholder="Colombo">
                        @error('city')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- District --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            District
                        </label>
                        <input type="text" name="district" value="{{ old('district', $branch->district) }}"
                            class="form-control @error('district') is-invalid @enderror" placeholder="Colombo">
                        @error('district')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Postal --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            Postal Code
                        </label>
                        <input type="text" name="postal_code"
                            value="{{ old('postal_code', $branch->postal_code) }}"
                            class="form-control @error('postal_code') is-invalid @enderror" placeholder="10250">
                        @error('postal_code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Country --}}
                    <div class="col-md-2">
                        <label class="form-label required fw-semibold">
                            Country
                        </label>
                        <input type="text" name="country" value="{{ old('country', $branch->country ?? 'LK') }}"
                            class="form-control text-uppercase @error('country') is-invalid @enderror" maxlength="2"
                            required>
                        @error('country')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-receipt text-info fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Billing & Regional Settings
                        </h2>
                        <div class="text-muted fs-8">
                            Currency, timezone, invoice numbering and branch taxes.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="row g-6">
                    {{-- Currency --}}
                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Currency
                        </label>
                        <select name="currency" class="form-select @error('currency') is-invalid @enderror" required
                            data-control="select2" data-hide-search="true">
                            @foreach (['LKR', 'USD', 'EUR', 'GBP', 'INR', 'AED'] as $currency)
                                <option value="{{ $currency }}" @selected(old('currency', $branch->currency ?? 'LKR') === $currency)>
                                    {{ $currency }}
                                </option>
                            @endforeach
                        </select>
                        @error('currency')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Timezone --}}
                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Timezone
                        </label>
                        <select name="timezone" class="form-select @error('timezone') is-invalid @enderror" required
                            data-control="select2">
                            @foreach (['Asia/Colombo', 'Asia/Kolkata', 'Asia/Dubai', 'Europe/London', 'America/New_York'] as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $branch->timezone ?? 'Asia/Colombo') === $timezone)>
                                    {{ $timezone }}
                                </option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Invoice Prefix --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Invoice Prefix
                        </label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-receipt-cutoff text-muted"></i>
                            </span>
                            <input type="text" name="invoice_prefix"
                                value="{{ old('invoice_prefix', $branch->invoice_prefix) }}"
                                class="form-control text-uppercase @error('invoice_prefix') is-invalid @enderror"
                                maxlength="20" placeholder="CMB">
                            @error('invoice_prefix')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="text-muted fs-8 mt-2">
                            Example: CMB-000001
                        </div>
                    </div>
                </div>

                <div class="separator separator-dashed my-7"></div>

                <label
                    class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer mb-6">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-percent text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fw-bold text-gray-900 mb-1">
                                Branch Tax
                            </div>
                            <div class="text-muted fs-8">
                                Enable tax calculations for invoices and POS transactions.
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch form-check-custom form-check-solid ms-4">
                        <input id="tax_enabled" class="form-check-input" type="checkbox" name="tax_enabled"
                            value="1" @checked(old('tax_enabled', $branch->tax_enabled))>
                    </div>
                </label>

                {{-- Tax Fields --}}
                <div id="tax_fields">
                    <div class="rounded-4 bg-light-warning p-5">
                        <div class="d-flex align-items-center mb-5">
                            <div class="symbol symbol-40px me-3">
                                <div class="symbol-label bg-white">
                                    <i class="bi bi-receipt text-warning"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-900">
                                    Tax Configuration
                                </div>
                                <div class="text-muted fs-8">
                                    These values will be applied to taxable branch transactions.
                                </div>
                            </div>
                        </div>

                        <div class="row g-5">
                            {{-- Tax Name --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Tax Name
                                </label>
                                <input type="text" name="tax_name"
                                    value="{{ old('tax_name', $branch->tax_name) }}"
                                    class="form-control bg-white @error('tax_name') is-invalid @enderror"
                                    placeholder="VAT">
                                @error('tax_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            {{-- Tax Rate --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Tax Rate
                                </label>
                                <div class="input-group">
                                    <input type="number" name="tax_rate"
                                        value="{{ old('tax_rate', $branch->tax_rate) }}"
                                        class="form-control bg-white @error('tax_rate') is-invalid @enderror"
                                        min="0" max="100" step="0.0001" placeholder="18">
                                    <span class="input-group-text bg-white">
                                        %
                                    </span>
                                    @error('tax_rate')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            {{-- Tax Number --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Tax Number
                                </label>
                                <input type="text" name="tax_number"
                                    value="{{ old('tax_number', $branch->tax_number) }}"
                                    class="form-control bg-white @error('tax_number') is-invalid @enderror"
                                    placeholder="Registration number">
                                @error('tax_number')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body text-center p-7">
                <div class="symbol symbol-75px mb-5">
                    <div class="symbol-label bg-light-primary rounded-4">
                        <i class="bi bi-shop text-primary fs-1"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-gray-900 mb-1">
                    {{ old('name', $branch->name) ?: 'New Branch' }}
                </h3>
                <div class="text-muted fs-8 mb-4">
                    {{ old('code', $branch->code) ?: 'Branch code not set' }}
                </div>
                @if ($branch->is_main)
                    <span class="badge badge-light-warning px-3 py-2">
                        <i class="bi bi-star-fill me-1"></i>
                        Main Branch
                    </span>
                @else
                    <span class="badge badge-light-primary px-3 py-2">
                        <i class="bi bi-building me-1"></i>
                        Salon Location
                    </span>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-warning">
                            <i class="bi bi-sliders text-warning fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Branch Settings
                        </h2>
                        <div class="text-muted fs-8">
                            Operational settings for this location.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-4">
                <label
                    class="d-flex align-items-start justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-40px me-4 flex-shrink-0">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-star-fill text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fw-bold text-gray-900 mb-1">
                                Main Branch
                            </div>
                            <div class="text-muted fs-8">
                                @if ($isEdit)
                                    Making this location the main branch will replace
                                    the current main branch.
                                @else
                                    The first created branch will automatically become
                                    the main branch.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch form-check-custom form-check-solid ms-3">
                        <input class="form-check-input" type="checkbox" name="is_main" value="1"
                            @checked(old('is_main', $branch->is_main))>
                    </div>
                </label>
            </div>
        </div>

        @if (
            $isEdit &&
                (app(\App\Services\BranchContext::class)->hasTenantWideBranchAccess(auth()->user()) ||
                    auth()->user()?->can('branches.manage_staff')))

            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-8">
                    <div class="card-title">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-people text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Assigned Users
                            </h2>
                            <div class="text-muted fs-8">
                                Users allowed to access this branch.
                            </div>
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge badge-light-info px-3 py-2">
                            {{ $assignableUsers->count() }}
                            {{ Str::plural('User', $assignableUsers->count()) }}
                        </span>
                    </div>
                </div>

                <div class="card-body pt-4">
                    @if ($assignableUsers->count())
                        <div class="d-flex flex-column gap-3">
                            @foreach ($assignableUsers as $assignableUser)
                                @php
                                    $isAssigned = in_array(
                                        $assignableUser->id,
                                        old('user_ids', $assignedUserIds),
                                        true,
                                    );
                                @endphp
                                <label
                                    class="d-flex align-items-center rounded-4 border {{ $isAssigned ? 'border-primary bg-light-primary' : 'border-gray-300' }} p-4 cursor-pointer">
                                    <div class="symbol symbol-40px me-3 flex-shrink-0">
                                        <div class="symbol-label bg-light-primary fw-bold text-primary">
                                            {{ strtoupper(substr($assignableUser->name, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-semibold text-gray-900">
                                            {{ $assignableUser->name }}
                                        </div>
                                        <div class="text-muted fs-8 text-truncate"
                                            title="{{ $assignableUser->email }}">
                                            {{ $assignableUser->email }}
                                        </div>
                                    </div>
                                    <div class="form-check form-check-custom form-check-solid ms-3">
                                        <input class="form-check-input" type="checkbox" name="user_ids[]"
                                            value="{{ $assignableUser->id }}" @checked($isAssigned)>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10">
                            <div class="symbol symbol-70px mb-5">
                                <div class="symbol-label bg-light">
                                    <i class="bi bi-people text-muted fs-1"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-gray-900 mb-2">
                                No Users Available
                            </h4>
                            <div class="text-muted fs-8">
                                There are currently no users available to assign.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm mt-8">
    <div class="card-body p-6">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-5">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px me-3">
                    <div class="symbol-label bg-light-primary">
                        <i class="bi bi-check2-square text-primary"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-semibold text-gray-900">
                        {{ $isEdit ? 'Update Branch Information' : 'Create New Branch' }}
                    </div>
                    <div class="text-muted fs-8">
                        Review the information before saving.
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3">
                <a href="{{ $isEdit ? route('branches.show', $branch) : route('branches.index') }}"
                    class="btn btn-light">
                    <i class="bi bi-x-lg me-2"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary px-7">
                    @if ($isEdit)
                        <i class="bi bi-check-circle me-2"></i>
                        Save Changes
                    @else
                        <i class="bi bi-building-add me-2"></i>
                        Create Branch
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taxToggle = document.getElementById('tax_enabled');
            const taxFields = document.getElementById('tax_fields');

            function toggleTaxFields() {
                if (!taxToggle || !taxFields) {
                    return;
                }
                if (taxToggle.checked) {
                    taxFields.classList.remove('d-none');
                } else {
                    taxFields.classList.add('d-none');
                }
            }
            toggleTaxFields();
            taxToggle?.addEventListener(
                'change',
                toggleTaxFields
            );
        });
    </script>
@endpush
