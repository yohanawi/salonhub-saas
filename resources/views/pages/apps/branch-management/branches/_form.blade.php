@php
    $isEdit = $branch->exists;
@endphp

<div class="row g-7">
    {{-- LEFT CONTENT --}}
    <div class="col-lg-8">
        {{-- General Information --}}
        <div class="card border-0 shadow-sm mb-7">
            <div class="card-header border-0 pt-7">
                <div class="card-title d-flex align-items-center gap-3">
                    <div class="symbol symbol-45px">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-building fs-2 text-primary"></i>
                        </div>
                    </div>

                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            General Information
                        </h2>
                        <div class="text-muted fs-7">
                            Basic details used to identify this salon branch.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="row g-5">
                    @if (! $isEdit && auth()->user()?->hasRole('Super Admin'))
                        <div class="col-12">
                            <label class="form-label required fw-semibold">
                                Salon / Tenant
                            </label>
                            <select name="tenant_id" class="form-select @error('tenant_id') is-invalid @enderror" required data-control="select2">
                                <option value="">
                                    Select salon
                                </option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                        {{ $tenant->name }}{{ $tenant->email ? ' - ' . $tenant->email : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tenant_id')
                                <div class="text-danger fs-7 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    @endif

                    <div class="col-md-8">
                        <label class="form-label required fw-semibold">
                            Branch Name
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-shop"></i>
                            </span>
                            <input type="text" name="name" value="{{ old('name', $branch->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="e.g. Colombo Branch" required>
                        </div>

                        @error('name')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Branch Code
                        </label>
                        <input type="text" name="code" value="{{ old('code', $branch->code) }}"
                            class="form-control text-uppercase @error('code') is-invalid @enderror" maxlength="20"
                            placeholder="CMB" required>
                        <div class="text-muted fs-8 mt-2">
                            Short unique code for this salon.
                        </div>

                        @error('code')
                            <div class="text-danger fs-7 mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Phone Number
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}"
                                class="form-control" placeholder="+94 11 234 5678">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" name="email" value="{{ old('email', $branch->email) }}"
                                class="form-control" placeholder="branch@example.com">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="card border-0 shadow-sm mb-7">
            <div class="card-header border-0 pt-7">
                <div class="card-title d-flex align-items-center gap-3">
                    <div class="symbol symbol-45px">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-geo-alt fs-2 text-success"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Location
                        </h2>
                        <div class="text-muted fs-7">
                            Physical address of this salon branch.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Address Line 1
                        </label>
                        <input type="text" name="address_line_1"
                            value="{{ old('address_line_1', $branch->address_line_1 ?? $branch->address) }}"
                            class="form-control" placeholder="Street address">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Address Line 2
                        </label>
                        <input type="text" name="address_line_2"
                            value="{{ old('address_line_2', $branch->address_line_2) }}" class="form-control"
                            placeholder="Apartment, floor, landmark...">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            City
                        </label>
                        <input type="text" name="city" value="{{ old('city', $branch->city) }}"
                            class="form-control" placeholder="Colombo">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            District
                        </label>
                        <input type="text" name="district" value="{{ old('district', $branch->district) }}"
                            class="form-control" placeholder="Colombo">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            Postal Code
                        </label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $branch->postal_code) }}"
                            class="form-control" placeholder="10250">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label required fw-semibold">
                            Country
                        </label>
                        <input type="text" name="country" value="{{ old('country', $branch->country ?? 'LK') }}"
                            class="form-control text-uppercase" maxlength="2" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing Settings --}}
        <div class="card border-0 shadow-sm mb-7">
            <div class="card-header border-0 pt-7">
                <div class="card-title d-flex align-items-center gap-3">
                    <div class="symbol symbol-45px">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-receipt fs-2 text-info"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Billing & Regional Settings
                        </h2>
                        <div class="text-muted fs-7">
                            Configure currency, timezone, invoices and tax settings.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Currency
                        </label>
                        <select name="currency" class="form-select" required data-control="select2"
                            data-hide-search="true">
                            @foreach (['LKR', 'USD', 'EUR', 'GBP', 'INR', 'AED'] as $currency)
                                <option value="{{ $currency }}" @selected(old('currency', $branch->currency ?? 'LKR') === $currency)>
                                    {{ $currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Timezone
                        </label>
                        <select name="timezone" class="form-select" required data-control="select2"
                            data-hide-search="true">
                            @foreach (['Asia/Colombo', 'Asia/Kolkata', 'Asia/Dubai', 'Europe/London', 'America/New_York'] as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $branch->timezone ?? 'Asia/Colombo') === $timezone)>
                                    {{ $timezone }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Invoice Prefix
                        </label>
                        <input type="text" name="invoice_prefix"
                            value="{{ old('invoice_prefix', $branch->invoice_prefix) }}"
                            class="form-control text-uppercase" maxlength="20" placeholder="CMB">
                        <div class="text-muted fs-8 mt-2">
                            Example: CMB-000001
                        </div>
                    </div>
                </div>

                <div class="separator separator-dashed my-7"></div>

                {{-- Tax Toggle --}}
                <div class="d-flex align-items-center justify-content-between bg-light rounded p-5 mb-6">
                    <div>
                        <div class="fw-bold text-gray-900">
                            Branch Tax
                        </div>
                        <div class="text-muted fs-7">
                            Enable tax calculations for this branch.
                        </div>
                    </div>
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input id="tax_enabled" class="form-check-input" type="checkbox" name="tax_enabled"
                            value="1" @checked(old('tax_enabled', $branch->tax_enabled))>
                    </label>
                </div>

                <div id="tax_fields">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                Tax Name
                            </label>
                            <input type="text" name="tax_name" value="{{ old('tax_name', $branch->tax_name) }}"
                                class="form-control" placeholder="VAT">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                Tax Rate
                            </label>
                            <div class="input-group">
                                <input type="number" name="tax_rate"
                                    value="{{ old('tax_rate', $branch->tax_rate) }}" class="form-control"
                                    min="0" max="100" step="0.0001" placeholder="18">
                                <span class="input-group-text">
                                    %
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                Tax Number
                            </label>
                            <input type="text" name="tax_number"
                                value="{{ old('tax_number', $branch->tax_number) }}" class="form-control"
                                placeholder="Registration number">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT SIDEBAR --}}
    <div class="col-lg-4">
        <div class="position-sticky" style="top: 100px;">
            {{-- Branch Settings --}}
            <div class="card border-0 shadow-sm mb-7">
                <div class="card-header border-0 pt-7">
                    <div class="card-title d-flex align-items-center gap-3">
                        <div class="symbol symbol-40px">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-sliders fs-3 text-warning"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-0">
                            Branch Settings
                        </h2>
                    </div>
                </div>

                <div class="card-body pt-3">
                    {{-- Main Branch --}}
                    <div class="border border-gray-300 rounded p-5">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_main" value="1"
                                @checked(old('is_main', $branch->is_main))>
                            <span class="form-check-label">
                                <span class="fw-bold text-gray-900 d-block">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    Main Branch
                                </span>
                                <span class="text-muted fs-8 d-block mt-1">
                                    @if ($isEdit)
                                        Making this branch main will automatically
                                        replace the current main branch.
                                    @else
                                        The first branch will automatically
                                        become the main branch.
                                    @endif
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Assigned Users --}}
            @if (
                $isEdit &&
                    (app(\App\Services\BranchContext::class)->hasTenantWideBranchAccess(auth()->user()) ||
                        auth()->user()?->can('branches.manage_staff')))
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title">
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Assigned Users
                                </h2>
                                <div class="text-muted fs-8">
                                    Users who can access this branch.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        @if ($assignableUsers->count())
                            <div class="d-flex flex-column gap-3 branch-users-list">
                                @foreach ($assignableUsers as $assignableUser)
                                    <label class="branch-user-item border rounded p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px me-3">
                                                <div class="symbol-label bg-light-primary fw-bold text-primary">
                                                    {{ strtoupper(substr($assignableUser->name, 0, 1)) }}
                                                </div>
                                            </div>

                                            <div class="flex-grow-1">
                                                <div class="fw-semibold text-gray-900">
                                                    {{ $assignableUser->name }}
                                                </div>
                                                <div class="text-muted fs-8">
                                                    {{ $assignableUser->email }}
                                                </div>
                                            </div>

                                            <input class="form-check-input" type="checkbox" name="user_ids[]"
                                                value="{{ $assignableUser->id }}" @checked(in_array($assignableUser->id, old('user_ids', $assignedUserIds), true))>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <i class="bi bi-people fs-1 text-muted"></i>
                                <div class="text-muted mt-3">
                                    No users available to assign.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Footer Actions --}}
<div class="card border-0 shadow-sm mt-7">
    <div class="card-body py-5">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-4">
            <div>
                <div class="fw-semibold text-gray-800">
                    {{ $isEdit ? 'Update Branch Information' : 'Create New Branch' }}
                </div>
                <div class="text-muted fs-8">
                    Review the information before saving.
                </div>
            </div>

            <div class="d-flex gap-3">
                <a href="{{ $isEdit ? route('branches.show', $branch) : route('branches.index') }}"
                    class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary px-7">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ $isEdit ? 'Save Changes' : 'Create Branch' }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .branch-user-item {
            cursor: pointer;
            transition: all .2s ease;
        }

        .branch-user-item:hover {
            background: var(--bs-gray-100);
            border-color: var(--bs-primary) !important;
            transform: translateY(-1px);
        }

        .branch-users-list {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .branch-users-list::-webkit-scrollbar {
            width: 5px;
        }

        .branch-users-list::-webkit-scrollbar-thumb {
            background: #d8d8d8;
            border-radius: 10px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const taxToggle = document.getElementById('tax_enabled');
            const taxFields = document.getElementById('tax_fields');

            function toggleTaxFields() {

                if (!taxToggle || !taxFields) {
                    return;
                }

                taxFields.style.display =
                    taxToggle.checked ? 'block' : 'none';
            }

            toggleTaxFields();

            taxToggle?.addEventListener('change', toggleTaxFields);

        });
    </script>
@endpush
