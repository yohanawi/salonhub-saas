@php
    $isEdit = $unit->exists;
@endphp

@csrf

@if ($method ?? false)
    @method($method)
@endif


<div class="row g-8">

    {{-- ========================================================= --}}
    {{-- MAIN FORM --}}
    {{-- ========================================================= --}}
    <div class="col-xl-8">

        <div class="card border-0 shadow-sm">

            {{-- Header --}}
            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-primary rounded-3">
                            <i class="bi bi-rulers text-primary fs-2"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            {{ $isEdit ? 'Edit Product Unit' : 'Create Product Unit' }}
                        </h3>

                        <div class="text-muted fs-8">
                            Define the measurement unit used for inventory quantities and products.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-5">

                {{-- ================================================= --}}
                {{-- SALON --}}
                {{-- ================================================= --}}
                @if ($tenants->isNotEmpty())

                    <div class="mb-8">

                        <div class="d-flex align-items-center mb-4">

                            <div class="symbol symbol-35px me-3">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-shop text-info"></i>
                                </div>
                            </div>

                            <div>
                                <div class="fw-bold text-gray-900">
                                    Unit Owner
                                </div>

                                <div class="text-muted fs-8">
                                    Select the salon that owns this measurement unit.
                                </div>
                            </div>

                        </div>


                        <label class="form-label required fw-semibold">
                            Salon
                        </label>

                        <select name="tenant_id"
                            class="form-select form-select-solid @error('tenant_id') is-invalid @enderror"
                            data-control="select2" required>
                            <option value="">
                                Select salon
                            </option>

                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $unit->tenant_id) === (string) $tenant->id)>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('tenant_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="separator separator-dashed mb-8"></div>

                @endif


                {{-- ================================================= --}}
                {{-- UNIT INFORMATION --}}
                {{-- ================================================= --}}
                <div class="mb-6">

                    <div class="d-flex align-items-center mb-5">

                        <div class="symbol symbol-35px me-3">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-info-circle text-primary"></i>
                            </div>
                        </div>

                        <div>

                            <div class="fw-bold text-gray-900">
                                Unit Information
                            </div>

                            <div class="text-muted fs-8">
                                Configure the unit name, symbol and measurement type.
                            </div>

                        </div>

                    </div>


                    <div class="row g-6">

                        {{-- Name --}}
                        <div class="col-md-5">

                            <label class="form-label required fw-semibold">
                                Unit Name
                            </label>

                            <div class="input-group">

                                <span class="input-group-text border-0 bg-light">
                                    <i class="bi bi-rulers text-muted"></i>
                                </span>

                                <input type="text" id="unit_name" name="name"
                                    value="{{ old('name', $unit->name) }}"
                                    class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Bottle"
                                    required>

                            </div>

                            @error('name')
                                <div class="text-danger fs-8 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="text-muted fs-8 mt-2">
                                Human-readable measurement name.
                            </div>

                        </div>


                        {{-- Symbol --}}
                        <div class="col-md-3">

                            <label class="form-label required fw-semibold">
                                Symbol
                            </label>

                            <div class="input-group">

                                <span class="input-group-text border-0 bg-light">
                                    <i class="bi bi-hash text-muted"></i>
                                </span>

                                <input type="text" id="unit_symbol" name="symbol"
                                    value="{{ old('symbol', $unit->symbol) }}"
                                    class="form-control form-control-solid @error('symbol') is-invalid @enderror"
                                    placeholder="e.g. btl" required>

                            </div>

                            @error('symbol')
                                <div class="text-danger fs-8 mt-2">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="text-muted fs-8 mt-2">
                                Short display code.
                            </div>

                        </div>


                        {{-- Type --}}
                        <div class="col-md-4">

                            <label class="form-label required fw-semibold">
                                Measurement Type
                            </label>

                            <select id="unit_type" name="type"
                                class="form-select form-select-solid @error('type') is-invalid @enderror"
                                data-control="select2" data-hide-search="true" required>

                                @foreach ($types as $type)
                                    <option value="{{ $type }}" @selected(old('type', $unit->type) === $type)>
                                        {{ str($type)->headline() }}
                                    </option>
                                @endforeach

                            </select>

                            @error('type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="text-muted fs-8 mt-2">
                                Defines how this unit is categorized.
                            </div>

                        </div>

                    </div>

                </div>


                <div class="separator separator-dashed my-8"></div>


                {{-- ================================================= --}}
                {{-- UNIT SETTINGS --}}
                {{-- ================================================= --}}
                <div>

                    <div class="d-flex align-items-center mb-5">

                        <div class="symbol symbol-35px me-3">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-gear-fill text-success"></i>
                            </div>
                        </div>

                        <div>

                            <div class="fw-bold text-gray-900">
                                Unit Settings
                            </div>

                            <div class="text-muted fs-8">
                                Control whether this unit is available for product assignment.
                            </div>

                        </div>

                    </div>


                    <label
                        class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 mb-0">

                        <div class="d-flex align-items-center">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-toggle-on text-success fs-3"></i>
                                </div>
                            </div>


                            <div>

                                <div class="fw-semibold text-gray-900">
                                    Active Unit
                                </div>

                                <div class="text-muted fs-8">
                                    Active units can be assigned to products.
                                </div>

                            </div>

                        </div>


                        <div class="form-check form-switch form-check-custom form-check-solid ms-4">

                            <input type="hidden" name="is_active" value="0">

                            <input id="unit_status" class="form-check-input" type="checkbox" name="is_active"
                                value="1" @checked(old('is_active', $unit->is_active ?? true))>

                        </div>

                    </label>

                </div>

            </div>


            {{-- Footer --}}
            <div class="card-footer border-0 d-flex flex-column flex-sm-row justify-content-end gap-3">

                <a href="{{ route('inventory.units.index') }}" class="btn btn-light">
                    <i class="bi bi-x-lg me-2"></i>
                    Cancel
                </a>


                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-2"></i>
                    {{ $submitLabel }}
                </button>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}
    <div class="col-xl-4">

        {{-- ===================================================== --}}
        {{-- LIVE PREVIEW --}}
        {{-- ===================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-eye text-primary"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Unit Preview
                        </h3>

                        <div class="text-muted fs-8">
                            Preview how this unit will appear.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-5">

                <div class="rounded-4 bg-light-primary p-6 text-center">

                    <div class="symbol symbol-70px mb-5">

                        <div class="symbol-label bg-white rounded-circle">
                            <i class="bi bi-rulers text-primary fs-1"></i>
                        </div>

                    </div>


                    <h3 id="unit_preview_name" class="fw-bolder text-gray-900 mb-2">
                        {{ old('name', $unit->name) ?: 'Unit Name' }}
                    </h3>


                    <div class="mb-4">

                        <span id="unit_preview_symbol" class="badge badge-light-info px-3 py-2">
                            {{ old('symbol', $unit->symbol) ?: 'symbol' }}
                        </span>

                    </div>


                    <div class="d-flex justify-content-center flex-wrap gap-2">

                        <span id="unit_preview_type" class="badge badge-light-primary">
                            {{ old('type', $unit->type) ? str(old('type', $unit->type))->headline() : 'Measurement Type' }}
                        </span>


                        <span id="unit_preview_status"
                            class="badge {{ old('is_active', $unit->is_active ?? true) ? 'badge-light-success' : 'badge-light-danger' }}">
                            {{ old('is_active', $unit->is_active ?? true) ? 'Active' : 'Inactive' }}
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- UNIT EXAMPLES --}}
        {{-- ===================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-lightbulb text-info"></i>
                        </div>
                    </div>


                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Unit Examples
                        </h3>

                        <div class="text-muted fs-8">
                            Common inventory measurement units.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="d-flex align-items-start mb-5">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-box text-primary"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold text-gray-900">
                            Piece
                        </div>

                        <div class="text-muted fs-8">
                            Individual products such as brushes or tools.
                        </div>
                    </div>

                </div>


                <div class="separator separator-dashed mb-5"></div>


                <div class="d-flex align-items-start mb-5">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-droplet text-info"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold text-gray-900">
                            Milliliter
                        </div>

                        <div class="text-muted fs-8">
                            Liquids such as shampoo, serum or developer.
                        </div>
                    </div>

                </div>


                <div class="separator separator-dashed mb-5"></div>


                <div class="d-flex align-items-start">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-warning">
                            <i class="bi bi-box-seam text-warning"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold text-gray-900">
                            Bottle / Pack
                        </div>

                        <div class="text-muted fs-8">
                            Packaged inventory sold or consumed as full units.
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ============================================================= --}}
{{-- LIVE PREVIEW --}}
{{-- ============================================================= --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const nameInput =
                document.getElementById('unit_name');

            const symbolInput =
                document.getElementById('unit_symbol');

            const typeSelect =
                document.getElementById('unit_type');

            const statusInput =
                document.getElementById('unit_status');


            const previewName =
                document.getElementById('unit_preview_name');

            const previewSymbol =
                document.getElementById('unit_preview_symbol');

            const previewType =
                document.getElementById('unit_preview_type');

            const previewStatus =
                document.getElementById('unit_preview_status');


            function headline(value) {

                return value
                    .replace(/[_-]+/g, ' ')
                    .replace(/\b\w/g, function(character) {
                        return character.toUpperCase();
                    });

            }


            function updatePreview() {

                if (previewName && nameInput) {

                    previewName.textContent =
                        nameInput.value.trim() || 'Unit Name';

                }


                if (previewSymbol && symbolInput) {

                    previewSymbol.textContent =
                        symbolInput.value.trim() || 'symbol';

                }


                if (previewType && typeSelect) {

                    previewType.textContent =
                        typeSelect.value ?
                        headline(typeSelect.value) :
                        'Measurement Type';

                }


                if (previewStatus && statusInput) {

                    previewStatus.classList.remove(
                        'badge-light-success',
                        'badge-light-danger'
                    );


                    if (statusInput.checked) {

                        previewStatus.textContent = 'Active';

                        previewStatus.classList.add(
                            'badge-light-success'
                        );

                    } else {

                        previewStatus.textContent = 'Inactive';

                        previewStatus.classList.add(
                            'badge-light-danger'
                        );

                    }

                }

            }


            nameInput?.addEventListener(
                'input',
                updatePreview
            );

            symbolInput?.addEventListener(
                'input',
                updatePreview
            );

            typeSelect?.addEventListener(
                'change',
                updatePreview
            );

            statusInput?.addEventListener(
                'change',
                updatePreview
            );


            updatePreview();

        });
    </script>
@endpush
