@csrf

@if ($method ?? false)
    @method($method)
@endif


@php
    $isEdit = $category->exists;
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
                            <i class="bi bi-tags-fill text-primary fs-2"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            {{ $isEdit ? 'Edit Product Category' : 'Create Product Category' }}
                        </h3>

                        <div class="text-muted fs-8">
                            Organize products into a clear and meaningful inventory group.
                        </div>
                    </div>

                </div>

            </div>


            {{-- Body --}}
            <div class="card-body pt-5">

                {{-- ================================================= --}}
                {{-- SALON --}}
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
                                    Category Owner
                                </div>

                                <div class="text-muted fs-8">
                                    Select the salon this product category belongs to.
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
                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $category->tenant_id) === (string) $tenant->id)>
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
                {{-- CATEGORY NAME --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label required fw-semibold">
                        Category Name
                    </label>

                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-tag-fill text-muted"></i>
                        </span>

                        <input type="text" name="name" id="category_name"
                            value="{{ old('name', $category->name) }}"
                            class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Hair Care"
                            required>

                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="text-muted fs-8 mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Use a short and easy-to-recognize category name.
                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- DESCRIPTION --}}
                {{-- ================================================= --}}
                <div class="mb-7">

                    <label class="form-label fw-semibold">
                        Description
                    </label>

                    <textarea name="description" id="category_description" rows="5"
                        class="form-control form-control-solid @error('description') is-invalid @enderror"
                        placeholder="Describe which products should belong to this category...">{{ old('description', $category->description) }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- SETTINGS --}}
                {{-- ================================================= --}}
                <div class="separator separator-dashed my-7"></div>


                <div class="d-flex align-items-center mb-5">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-sliders text-info"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-bold text-gray-900">
                            Category Settings
                        </div>

                        <div class="text-muted fs-8">
                            Control display priority and availability.
                        </div>
                    </div>

                </div>


                <div class="row g-5">

                    {{-- Sort Order --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Sort Order
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-sort-numeric-down text-muted"></i>
                            </span>

                            <input type="number" min="0" name="sort_order"
                                value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                                class="form-control @error('sort_order') is-invalid @enderror">

                            @error('sort_order')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="text-muted fs-8 mt-2">
                            Lower values can be displayed first.
                        </div>

                    </div>


                    {{-- Active Status --}}
                    <div class="col-md-6">

                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 h-100">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-toggle-on text-success"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="fw-bold text-gray-900 mb-1">
                                        Active Category
                                    </div>

                                    <div class="text-muted fs-8">
                                        Allow this category to be used in the product catalog.
                                    </div>
                                </div>

                            </div>


                            <div class="form-check form-switch form-check-custom form-check-solid ms-3">

                                <input type="hidden" name="is_active" value="0">

                                <input id="category_status" class="form-check-input" type="checkbox" name="is_active"
                                    value="1" @checked(old('is_active', $category->is_active ?? true))>

                            </div>

                        </label>

                    </div>

                </div>

            </div>


            {{-- Footer --}}
            <div class="card-footer border-0 d-flex flex-column flex-sm-row justify-content-end gap-3">

                <a href="{{ route('inventory.categories.index') }}" class="btn btn-light">
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
    {{-- RIGHT SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">

        {{-- ======================================================== --}}
        {{-- LIVE PREVIEW --}}
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
                            Category Preview
                        </h3>

                        <div class="text-muted fs-8">
                            Preview how this category is represented.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body text-center pt-5">

                <div class="symbol symbol-80px mb-5">

                    <div class="symbol-label bg-light-primary rounded-4">
                        <i class="bi bi-tag-fill text-primary fs-1"></i>
                    </div>

                </div>


                <h3 id="category_preview_name" class="fw-bold text-gray-900 mb-2">
                    {{ old('name', $category->name ?: 'New Category') }}
                </h3>


                <div id="category_preview_description" class="text-muted fs-8 mb-5">
                    {{ old('description', $category->description ?: 'Category description will appear here.') }}
                </div>


                <span id="category_status_badge"
                    class="badge badge-light-{{ old('is_active', $category->is_active ?? true) ? 'success' : 'secondary' }} px-3 py-2">
                    <i class="bi bi-circle-fill fs-9 me-1"></i>

                    {{ old('is_active', $category->is_active ?? true) ? 'Active' : 'Inactive' }}
                </span>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- ORGANIZATION TIPS --}}
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
                            Category Tips
                        </h3>

                        <div class="text-muted fs-8">
                            Keep your product catalog easy to navigate.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="d-flex align-items-start mb-5">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-check2 text-success"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold text-gray-900 mb-1">
                            Keep Names Simple
                        </div>

                        <div class="text-muted fs-8">
                            Use names staff and customers can recognize immediately.
                        </div>
                    </div>

                </div>


                <div class="separator separator-dashed mb-5"></div>


                <div class="d-flex align-items-start mb-5">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-box-seam text-info"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold text-gray-900 mb-1">
                            Group Similar Products
                        </div>

                        <div class="text-muted fs-8">
                            Put products with similar purposes into the same category.
                        </div>
                    </div>

                </div>


                <div class="separator separator-dashed mb-5"></div>


                <div class="d-flex align-items-start">

                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-sort-down text-primary"></i>
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold text-gray-900 mb-1">
                            Use Sort Order
                        </div>

                        <div class="text-muted fs-8">
                            Prioritize commonly used categories with lower sort values.
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
                document.getElementById('category_name');

            const descriptionInput =
                document.getElementById('category_description');

            const statusInput =
                document.getElementById('category_status');

            const previewName =
                document.getElementById('category_preview_name');

            const previewDescription =
                document.getElementById('category_preview_description');

            const statusBadge =
                document.getElementById('category_status_badge');


            /*
            |--------------------------------------------------------------------------
            | Category Name
            |--------------------------------------------------------------------------
            */
            nameInput?.addEventListener('input', function() {

                if (!previewName) {
                    return;
                }

                previewName.textContent =
                    this.value.trim() || 'New Category';

            });


            /*
            |--------------------------------------------------------------------------
            | Category Description
            |--------------------------------------------------------------------------
            */
            descriptionInput?.addEventListener('input', function() {

                if (!previewDescription) {
                    return;
                }

                previewDescription.textContent =
                    this.value.trim() ||
                    'Category description will appear here.';

            });


            /*
            |--------------------------------------------------------------------------
            | Status Preview
            |--------------------------------------------------------------------------
            */
            function updateStatusPreview() {

                if (!statusInput || !statusBadge) {
                    return;
                }

                const active = statusInput.checked;

                statusBadge.innerHTML =
                    '<i class="bi bi-circle-fill fs-9 me-1"></i>' +
                    (active ? 'Active' : 'Inactive');

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
                updateStatusPreview
            );

            updateStatusPreview();

        });
    </script>
@endpush
