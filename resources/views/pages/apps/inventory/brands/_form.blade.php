@csrf

@if ($method ?? false)
    @method($method)
@endif


@php
    $isEdit = $brand->exists;
@endphp


<div class="row g-8">

    {{-- ============================================================ --}}
    {{-- MAIN FORM --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">

        <div class="card border-0 shadow-sm">

            {{-- Header --}}
            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-award-fill text-primary fs-2"></i>
                        </div>
                    </div>

                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            {{ $isEdit ? 'Edit Product Brand' : 'Create Product Brand' }}
                        </h3>

                        <div class="text-muted fs-8">
                            Define the brand identity used across your inventory catalog.
                        </div>

                    </div>

                </div>

            </div>


            {{-- Body --}}
            <div class="card-body pt-5">

                {{-- ================================================= --}}
                {{-- SALON OWNER --}}
                {{-- ================================================= --}}
                @if ($tenants->isNotEmpty())

                    <div class="rounded-4 bg-light-primary p-5 mb-7">

                        <div class="d-flex align-items-center mb-5">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-white">
                                    <i class="bi bi-shop text-primary"></i>
                                </div>
                            </div>

                            <div>

                                <div class="fw-bold text-gray-900 mb-1">
                                    Brand Owner
                                </div>

                                <div class="text-muted fs-8">
                                    Select the salon this product brand belongs to.
                                </div>

                            </div>

                        </div>


                        <label class="form-label required fw-semibold">
                            Salon / Tenant
                        </label>

                        <select name="tenant_id" class="form-select bg-white @error('tenant_id') is-invalid @enderror"
                            data-control="select2" data-hide-search="true" required>
                            <option value="">
                                Select salon
                            </option>

                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $brand->tenant_id) === (string) $tenant->id)>
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

                @endif


                {{-- ================================================= --}}
                {{-- BRAND NAME --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label required fw-semibold">
                        Brand Name
                    </label>

                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-award text-muted"></i>
                        </span>

                        <input type="text" name="name" id="brand_name" value="{{ old('name', $brand->name) }}"
                            class="form-control @error('name') is-invalid @enderror" placeholder="e.g. L'Oréal"
                            required>

                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="text-muted fs-8 mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Use the official or commonly recognized brand name.
                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- DESCRIPTION --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label fw-semibold">
                        Description
                    </label>

                    <textarea name="description" id="brand_description" rows="5"
                        class="form-control form-control-solid @error('description') is-invalid @enderror"
                        placeholder="Add a short description about this brand...">{{ old('description', $brand->description) }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="separator separator-dashed my-7"></div>


                {{-- ================================================= --}}
                {{-- BRAND SETTINGS --}}
                {{-- ================================================= --}}
                <div class="d-flex align-items-center mb-5">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-sliders text-info"></i>
                        </div>
                    </div>

                    <div>

                        <div class="fw-bold text-gray-900">
                            Brand Settings
                        </div>

                        <div class="text-muted fs-8">
                            Control whether this brand is available in your catalog.
                        </div>

                    </div>

                </div>


                <label class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-toggle-on text-success fs-3"></i>
                            </div>
                        </div>


                        <div>

                            <div class="fw-bold text-gray-900 mb-1">
                                Active Brand
                            </div>

                            <div class="text-muted fs-8">
                                Allow this brand to be selected when managing products.
                            </div>

                        </div>

                    </div>


                    <div class="form-check form-switch form-check-custom form-check-solid ms-3">

                        <input type="hidden" name="is_active" value="0">

                        <input id="brand_status" class="form-check-input" type="checkbox" name="is_active"
                            value="1" @checked(old('is_active', $brand->is_active ?? true))>

                    </div>

                </label>

            </div>


            {{-- Footer --}}
            <div class="card-footer border-0 d-flex flex-column flex-sm-row justify-content-end gap-3">

                <a href="{{ route('inventory.brands.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>
                    Cancel
                </a>


                <button type="submit" class="btn btn-primary px-7">
                    <i class="bi bi-check2-circle me-2"></i>
                    {{ $submitLabel }}
                </button>

            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">

        {{-- ======================================================== --}}
        {{-- BRAND PREVIEW --}}
        {{-- ======================================================== --}}
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
                            Brand Preview
                        </h3>

                        <div class="text-muted fs-8">
                            Preview how the brand is represented.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body text-center pt-5">

                <div class="symbol symbol-90px mb-5">

                    <div class="symbol-label bg-light-primary rounded-4">
                        <i class="bi bi-award-fill text-primary fs-1"></i>
                    </div>

                </div>


                <h3 id="brand_preview_name" class="fw-bold text-gray-900 mb-2">
                    {{ old('name', $brand->name ?: 'New Brand') }}
                </h3>


                <div id="brand_preview_description" class="text-muted fs-8 mb-5">
                    {{ old('description', $brand->description ?: 'Brand description will appear here.') }}
                </div>


                <span id="brand_status_badge"
                    class="badge badge-light-{{ old('is_active', $brand->is_active ?? true) ? 'success' : 'secondary' }} px-3 py-2">
                    {{ old('is_active', $brand->is_active ?? true) ? 'Active' : 'Inactive' }}
                </span>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- BRAND GUIDELINES --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-warning">
                            <i class="bi bi-lightbulb text-warning"></i>
                        </div>
                    </div>

                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Brand Guidelines
                        </h3>

                        <div class="text-muted fs-8">
                            Keep catalog brand data consistent.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- Official Name --}}
                <div class="d-flex align-items-start mb-5">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-check2 text-success"></i>
                        </div>
                    </div>

                    <div>

                        <div class="fw-semibold text-gray-900 mb-1">
                            Use Official Names
                        </div>

                        <div class="text-muted fs-8">
                            Enter the brand name exactly as customers recognize it.
                        </div>

                    </div>

                </div>


                <div class="separator separator-dashed mb-5"></div>


                {{-- Avoid duplicates --}}
                <div class="d-flex align-items-start mb-5">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-copy text-info"></i>
                        </div>
                    </div>

                    <div>

                        <div class="fw-semibold text-gray-900 mb-1">
                            Avoid Duplicates
                        </div>

                        <div class="text-muted fs-8">
                            Keep one brand record for products from the same manufacturer.
                        </div>

                    </div>

                </div>


                <div class="separator separator-dashed mb-5"></div>


                {{-- Product grouping --}}
                <div class="d-flex align-items-start">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-boxes text-primary"></i>
                        </div>
                    </div>

                    <div>

                        <div class="fw-semibold text-gray-900 mb-1">
                            Improve Product Search
                        </div>

                        <div class="text-muted fs-8">
                            Consistent brands make product filtering and reporting easier.
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ================================================================ --}}
{{-- LIVE PREVIEW --}}
{{-- ================================================================ --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const nameInput =
                document.getElementById('brand_name');

            const descriptionInput =
                document.getElementById('brand_description');

            const statusInput =
                document.getElementById('brand_status');

            const previewName =
                document.getElementById('brand_preview_name');

            const previewDescription =
                document.getElementById('brand_preview_description');

            const statusBadge =
                document.getElementById('brand_status_badge');


            /*
            |--------------------------------------------------------------------------
            | Brand Name Preview
            |--------------------------------------------------------------------------
            */
            nameInput?.addEventListener('input', function() {

                if (!previewName) {
                    return;
                }

                previewName.textContent =
                    this.value.trim() || 'New Brand';

            });


            /*
            |--------------------------------------------------------------------------
            | Description Preview
            |--------------------------------------------------------------------------
            */
            descriptionInput?.addEventListener('input', function() {

                if (!previewDescription) {
                    return;
                }

                previewDescription.textContent =
                    this.value.trim() ||
                    'Brand description will appear here.';

            });


            /*
            |--------------------------------------------------------------------------
            | Status Preview
            |--------------------------------------------------------------------------
            */
            function updateBrandStatus() {

                if (!statusInput || !statusBadge) {
                    return;
                }

                const active = statusInput.checked;

                statusBadge.textContent =
                    active ? 'Active' : 'Inactive';

                statusBadge.classList.toggle(
                    'badge-light-success',
                    active
                );

                statusBadge.classList.toggle(
                    'badge-light-secondary',
                    !active
                );

            }


            statusInput?.addEventListener(
                'change',
                updateBrandStatus
            );

            updateBrandStatus();

        });
    </script>
@endpush
