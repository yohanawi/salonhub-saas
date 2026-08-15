@php
    $isEdit = $category->exists;
@endphp

{{-- Validation Alert --}}
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">
        <span class="symbol symbol-45px me-4 flex-shrink-0">
            <span class="symbol-label bg-light-danger rounded-circle">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
            </span>
        </span>
        <div>
            <div class="fw-bold text-gray-900 mb-1">
                Please review the category details
            </div>
            <div class="text-gray-700">
                {{ $errors->first() }}
            </div>
        </div>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-xl-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-0 pt-8 pb-5">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <span class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-light-info rounded-3">
                                <i class="bi bi-tags fs-2 text-info"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                {{ $isEdit ? 'Edit Service Category' : 'Create Service Category' }}
                            </h2>
                            <div class="text-muted fs-7">
                                Organize related salon services into a clear category.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-5">
                <div class="row g-8">
                    <div class="col-lg-8">
                        <div class="row g-6">
                            @if (($tenants ?? collect())->isNotEmpty())
                                <div class="col-12">
                                    <label class="form-label required fw-semibold">
                                        Salon / Tenant
                                    </label>
                                    <select name="tenant_id" data-control="select2" data-hide-search="true"
                                        class="form-select form-select-solid @error('tenant_id') is-invalid @enderror"
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
                                    <div class="text-muted fs-8 mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        This category will belong only to the selected salon.
                                    </div>
                                    @error('tenant_id')
                                        <div class="text-danger fs-7 mt-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @endif

                            <div class="col-md-8">
                                <label class="form-label required fw-semibold">
                                    Category Name
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 translate-middle-y ms-4 text-muted">
                                        <i class="bi bi-tag fs-5"></i>
                                    </span>
                                    <input type="text" name="name" id="category_name"
                                        value="{{ old('name', $category->name) }}"
                                        class="form-control form-control-solid ps-11 @error('name') is-invalid @enderror"
                                        placeholder="e.g. Hair, Nails, Facial" required>
                                </div>
                                <div class="text-muted fs-8 mt-2">
                                    Use a short name that clearly groups related services.
                                </div>
                                @error('name')
                                    <div class="text-danger fs-7 mt-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Sort Order
                                </label>
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">
                                        <i class="bi bi-sort-numeric-down"></i>
                                    </span>
                                    <input type="number" name="sort_order"
                                        value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-control"
                                        min="0">
                                </div>
                                <div class="text-muted fs-8 mt-2">
                                    Lower values appear first.
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Description
                                </label>
                                <textarea name="description" id="category_description" class="form-control form-control-solid" rows="5"
                                    placeholder="Describe what type of services belong in this category...">{{ old('description', $category->description) }}</textarea>
                                <div class="text-muted fs-8 mt-2">
                                    Optional, but useful for administrators and future booking interfaces.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="d-flex flex-column gap-6">
                            <div class="border rounded-3 p-5 bg-light">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="me-4">
                                        <div class="fw-bold text-gray-900 mb-1">
                                            Category Status
                                        </div>
                                        <div class="text-muted fs-8">
                                            Active categories can be used when creating services.
                                        </div>
                                    </div>
                                    <label class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="is_active"
                                            id="category_status" value="1" @checked(old('is_active', $category->is_active ?? true))>
                                    </label>
                                </div>
                                <div class="separator separator-dashed my-4"></div>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="category_status_badge"
                                        class="badge px-3 py-2
                                        {{ old('is_active', $category->is_active ?? true) ? 'badge-light-success' : 'badge-light-danger' }}">
                                        {{ old('is_active', $category->is_active ?? true) ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="text-muted fs-8">
                                        Current state
                                    </span>
                                </div>
                            </div>

                            <div class="border rounded-3 p-5 category-preview">
                                <div class="text-muted fw-semibold fs-8 text-uppercase mb-4">
                                    Category Preview
                                </div>
                                <div class="d-flex align-items-center mb-4">
                                    <span class="symbol symbol-45px me-3">
                                        <span class="symbol-label bg-light-info">
                                            <i class="bi bi-tag-fill fs-3 text-info"></i>
                                        </span>
                                    </span>
                                    <div>
                                        <div id="category_preview_name" class="fw-bold text-gray-900 fs-6">
                                            {{ old('name', $category->name ?: 'Category Name') }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            Service category
                                        </div>
                                    </div>
                                </div>
                                <div id="category_preview_description" class="text-gray-700 fs-8 lh-lg">
                                    {{ old('description', $category->description ?: 'Your category description will appear here.') }}
                                </div>
                            </div>

                            <div class="rounded-3 bg-light-info p-5">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-lightbulb-fill text-info fs-3 me-3"></i>
                                    <span class="fw-bold text-gray-900">
                                        Good category examples
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge badge-light-info">
                                        Hair
                                    </span>
                                    <span class="badge badge-light-info">
                                        Nails
                                    </span>
                                    <span class="badge badge-light-info">
                                        Facial
                                    </span>
                                    <span class="badge badge-light-info">
                                        Massage
                                    </span>
                                    <span class="badge badge-light-info">
                                        Bridal
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-7">
                <div class="separator separator-dashed mb-6"></div>
                <div class="d-flex flex-column flex-sm-row justify-content-end gap-3">
                    <a href="{{ route('service-categories.index') }}" class="btn btn-light px-6">
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

@push('styles')
    <style>
        .category-preview {
            background:
                linear-gradient(135deg,
                    rgba(var(--bs-info-rgb), .05),
                    transparent);

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .category-preview:hover {
            border-color: rgba(var(--bs-info-rgb), .35) !important;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .04);
            transform: translateY(-1px);
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('category_name');
            const descriptionInput = document.getElementById('category_description');
            const statusInput = document.getElementById('category_status');
            const previewName = document.getElementById('category_preview_name');
            const previewDescription = document.getElementById('category_preview_description');
            const statusBadge = document.getElementById('category_status_badge');

            /*
             * Live Category Preview
             */
            nameInput?.addEventListener('input', function() {
                previewName.textContent =
                    this.value.trim() ?
                    this.value :
                    'Category Name';
            });

            descriptionInput?.addEventListener(
                'input',
                function() {
                    previewDescription.textContent =
                        this.value.trim() ?
                        this.value :
                        'Your category description will appear here.';
                }
            );

            /*
             * Live Status Badge
             */
            statusInput?.addEventListener(
                'change',
                function() {
                    const active = this.checked;
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
            );
        });
    </script>
@endpush
