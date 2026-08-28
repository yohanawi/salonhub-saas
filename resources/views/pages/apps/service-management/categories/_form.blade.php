@php
    $isEdit = $category->exists;
@endphp

@if ($errors->any())

    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">

        <div class="symbol symbol-45px me-4 flex-shrink-0">
            <div class="symbol-label bg-light-danger">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
            </div>
        </div>

        <div>
            <div class="fw-bold text-gray-900 mb-1">
                Please review the category details
            </div>

            <div class="text-gray-700">
                {{ $errors->first() }}
            </div>

            @if ($errors->count() > 1)
                <div class="text-muted fs-8 mt-1">
                    Check the highlighted fields before saving.
                </div>
            @endif
        </div>

    </div>

@endif


<div class="row g-8">

    {{-- ============================================================ --}}
    {{-- LEFT CONTENT --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">

        <div class="card border-0 shadow-sm">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}
            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-info rounded-3">
                            <i class="bi bi-tags-fill fs-2 text-info"></i>
                        </div>
                    </div>

                    <div>

                        <h2 class="fw-bold text-gray-900 mb-1">
                            {{ $isEdit ? 'Edit Service Category' : 'Create Service Category' }}
                        </h2>

                        <div class="text-muted fs-8">
                            Organize related salon services into a clear catalog group.
                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- BODY --}}
            {{-- ===================================================== --}}
            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- ================================================= --}}
                    {{-- TENANT --}}
                    {{-- ================================================= --}}
                    @if (($tenants ?? collect())->isNotEmpty())

                        <div class="col-12">
                            <div
                                class="d-flex align-items-center justify-content-between p-4 rounded-4 bg-light-primary px-5">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-white">
                                            <i class="bi bi-shop text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-900 mb-1">
                                            Category Owner
                                        </div>
                                        <div class="text-muted fs-8">
                                            Select the salon that will own this service category.
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <select name="tenant_id" data-control="select2" data-hide-search="true"
                                        class="form-select bg-white @error('tenant_id') is-invalid @enderror w-400px"
                                        required>
                                        <option value="">
                                            Select salon
                                        </option>
                                        @foreach ($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $category->tenant_id) === (string) $tenant->id)>
                                                {{ $tenant->name }}

                                                @if ($tenant->email)
                                                    — {{ $tenant->email }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tenant_id')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif


                    {{-- ================================================= --}}
                    {{-- CATEGORY NAME --}}
                    {{-- ================================================= --}}
                    <div class="col-md-8">

                        <label class="form-label required fw-semibold">
                            Category Name
                        </label>


                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-tag text-muted"></i>
                            </span>


                            <input type="text" name="name" id="category_name"
                                value="{{ old('name', $category->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="e.g. Hair, Nails, Facial" required>


                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="text-muted fs-8 mt-2">
                            Use a short, recognizable name for this service group.
                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- SORT ORDER --}}
                    {{-- ================================================= --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Sort Order
                        </label>


                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-sort-numeric-down text-muted"></i>
                            </span>


                            <input type="number" name="sort_order"
                                value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                                class="form-control @error('sort_order') is-invalid @enderror" min="0">


                            @error('sort_order')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="text-muted fs-8 mt-2">
                            Lower values appear first.
                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- DESCRIPTION --}}
                    {{-- ================================================= --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Description
                        </label>


                        <textarea name="description" id="category_description"
                            class="form-control form-control-solid @error('description') is-invalid @enderror" rows="5"
                            placeholder="Describe what type of services belong in this category...">{{ old('description', $category->description) }}</textarea>


                        @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror


                        <div class="rounded-3 bg-light p-4 mt-4">

                            <div class="d-flex align-items-start">

                                <div class="symbol symbol-35px me-3 flex-shrink-0">
                                    <div class="symbol-label bg-white">
                                        <i class="bi bi-lightbulb text-warning"></i>
                                    </div>
                                </div>

                                <div>

                                    <div class="fw-semibold text-gray-900 fs-8 mb-1">
                                        Description Tip
                                    </div>

                                    <div class="text-muted fs-8">
                                        A useful description helps administrators understand
                                        which services belong in this category.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- STATUS --}}
                    {{-- ================================================= --}}
                    <div class="col-12">

                        <div class="separator separator-dashed my-2"></div>


                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 cursor-pointer mt-5">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">

                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-toggle-on text-success fs-3"></i>
                                    </div>

                                </div>


                                <div>

                                    <div class="fw-bold text-gray-900 mb-1">
                                        Category Status
                                    </div>

                                    <div class="text-muted fs-8">
                                        Active categories can be assigned when creating services.
                                    </div>

                                </div>

                            </div>


                            <div class="d-flex align-items-center gap-3">

                                <span id="category_status_badge"
                                    class="badge px-3 py-2 {{ old('is_active', $category->is_active ?? true) ? 'badge-light-success' : 'badge-light-danger' }}">
                                    {{ old('is_active', $category->is_active ?? true) ? 'Active' : 'Inactive' }}
                                </span>


                                <div class="form-check form-switch form-check-custom form-check-solid">

                                    <input class="form-check-input" type="checkbox" name="is_active"
                                        id="category_status" value="1" @checked(old('is_active', $category->is_active ?? true))>

                                </div>

                            </div>

                        </label>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- FOOTER --}}
            {{-- ===================================================== --}}
            <div class="card-footer border-0 pt-2 pb-7">

                <div class="separator separator-dashed mb-6"></div>


                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-4">

                    <div class="text-muted fs-8">

                        <i class="bi bi-info-circle me-1"></i>

                        Required fields must be completed before saving.

                    </div>


                    <div class="d-flex gap-3">

                        <a href="{{ route('service-categories.index') }}" class="btn btn-light">
                            <i class="bi bi-x-lg me-2"></i>
                            Cancel
                        </a>


                        <button type="submit" class="btn btn-primary px-7">
                            <i class="bi {{ $isEdit ? 'bi-check2-circle' : 'bi-plus-circle' }} me-2"></i>

                            {{ $isEdit ? 'Save Category Changes' : 'Create Category' }}
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- RIGHT SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">

        {{-- ======================================================== --}}
        {{-- CATEGORY PREVIEW --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-eye text-info fs-3"></i>
                        </div>
                    </div>

                    <div>

                        <h3 class="fw-bold text-gray-900 mb-1">
                            Category Preview
                        </h3>

                        <div class="text-muted fs-8">
                            Live preview of this catalog group.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="rounded-4 border border-gray-300 p-5">

                    <div class="d-flex align-items-center mb-5">

                        <div class="symbol symbol-50px me-4">

                            <div class="symbol-label bg-light-info rounded-3">
                                <i class="bi bi-tag-fill fs-3 text-info"></i>
                            </div>

                        </div>


                        <div>

                            <div id="category_preview_name" class="fw-bold text-gray-900 fs-5 mb-1">
                                {{ old('name', $category->name ?: 'Category Name') }}
                            </div>

                            <span class="badge badge-light-info">
                                Service Category
                            </span>

                        </div>

                    </div>


                    <div class="separator separator-dashed mb-5"></div>


                    <div class="text-muted fs-9 fw-semibold text-uppercase mb-2">
                        Description
                    </div>


                    <div id="category_preview_description" class="text-gray-700 fs-8 lh-lg">
                        {{ old('description', $category->description ?: 'Your category description will appear here.') }}
                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- CATEGORY EXAMPLES --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body p-6">

                <div class="d-flex align-items-center mb-5">

                    <div class="symbol symbol-40px me-3">
                        <div class="symbol-label bg-light-warning">
                            <i class="bi bi-lightbulb-fill text-warning"></i>
                        </div>
                    </div>

                    <div>

                        <div class="fw-bold text-gray-900 mb-1">
                            Good Category Examples
                        </div>

                        <div class="text-muted fs-8">
                            Keep categories broad and easy to understand.
                        </div>

                    </div>

                </div>


                <div class="d-flex flex-wrap gap-2">

                    <span class="badge badge-light-primary px-3 py-2">
                        <i class="bi bi-scissors me-1"></i>
                        Hair
                    </span>

                    <span class="badge badge-light-info px-3 py-2">
                        Nails
                    </span>

                    <span class="badge badge-light-success px-3 py-2">
                        Facial
                    </span>

                    <span class="badge badge-light-warning px-3 py-2">
                        Massage
                    </span>

                    <span class="badge badge-light-danger px-3 py-2">
                        Bridal
                    </span>

                    <span class="badge badge-light px-3 py-2">
                        Makeup
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ================================================================ --}}
{{-- SCRIPTS --}}
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
            | Live Category Name Preview
            |--------------------------------------------------------------------------
            */

            nameInput?.addEventListener('input', function() {

                if (!previewName) {
                    return;
                }

                previewName.textContent =
                    this.value.trim() || 'Category Name';

            });


            /*
            |--------------------------------------------------------------------------
            | Live Description Preview
            |--------------------------------------------------------------------------
            */

            descriptionInput?.addEventListener('input', function() {

                if (!previewDescription) {
                    return;
                }

                previewDescription.textContent =
                    this.value.trim() ||
                    'Your category description will appear here.';

            });


            /*
            |--------------------------------------------------------------------------
            | Live Status Preview
            |--------------------------------------------------------------------------
            */

            function refreshCategoryStatus() {

                if (!statusInput || !statusBadge) {
                    return;
                }

                const active =
                    statusInput.checked;


                statusBadge.textContent =
                    active ?
                    'Active' :
                    'Inactive';


                statusBadge.classList.toggle(
                    'badge-light-success',
                    active
                );


                statusBadge.classList.toggle(
                    'badge-light-danger',
                    !active
                );

            }


            statusInput?.addEventListener(
                'change',
                refreshCategoryStatus
            );


            refreshCategoryStatus();

        });
    </script>
@endpush
