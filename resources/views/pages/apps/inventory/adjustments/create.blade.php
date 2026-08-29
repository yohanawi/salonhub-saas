<x-default-layout>

    @section('title')
        New Stock Adjustment
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.adjustments.create') }}
    @endsection


    <div id="kt_app_content_container">

        {{-- ========================================================= --}}
        {{-- ALERTS --}}
        {{-- ========================================================= --}}
        @include('pages/apps.inventory.partials._alerts')


        {{-- ========================================================= --}}
        {{-- HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mb-5">

            <div class="card-body p-4 p-lg-6">

                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-7">

                    <div class="d-flex align-items-start">

                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-sliders text-primary fs-1"></i>
                            </div>
                        </div>


                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    New Stock Adjustment
                                </h3>

                                <span class="badge badge-light-warning px-3 py-2">
                                    Inventory Reconciliation
                                </span>

                            </div>


                            <div class="text-muted fs-7">
                                Record physical inventory counts and reconcile differences
                                against the quantities stored in the system.
                            </div>

                        </div>

                    </div>


                    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-2"></i>
                        Adjustment History
                    </a>

                </div>

            </div>

        </div>


        <form method="POST" action="{{ route('inventory.adjustments.store') }}">

            @csrf


            <div class="row g-8">

                {{-- ================================================= --}}
                {{-- MAIN CONTENT --}}
                {{-- ================================================= --}}
                <div class="col-xl-9">

                    {{-- ============================================= --}}
                    {{-- ADJUSTMENT DETAILS --}}
                    {{-- ============================================= --}}
                    <div class="card border-0 shadow-sm mb-8">

                        <div class="card-header border-0 pt-8">

                            <div class="card-title">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-card-checklist text-primary fs-3"></i>
                                    </div>
                                </div>


                                <div>

                                    <h3 class="fw-bold text-gray-900 mb-1">
                                        Adjustment Details
                                    </h3>

                                    <div class="text-muted fs-8">
                                        Define where the adjustment is happening and why.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="card-body pt-5">

                            <div class="row g-6">

                                {{-- Branch --}}
                                <div class="col-lg-4">

                                    <label class="form-label required fw-semibold">
                                        Branch
                                    </label>

                                    <select name="branch_id"
                                        class="form-select form-select-solid @error('branch_id') is-invalid @enderror"
                                        data-control="select2" required>
                                        <option value="">
                                            Select branch
                                        </option>

                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach

                                    </select>

                                    @error('branch_id')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror


                                    <div class="text-muted fs-8 mt-2">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Inventory will be adjusted for this branch.
                                    </div>

                                </div>


                                {{-- Reason --}}
                                <div class="col-lg-4">

                                    <label class="form-label required fw-semibold">
                                        Adjustment Reason
                                    </label>

                                    <select name="reason"
                                        class="form-select form-select-solid @error('reason') is-invalid @enderror"
                                        data-control="select2" data-hide-search="true" required>

                                        @foreach ($reasons as $reason)
                                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>
                                                {{ str($reason)->headline() }}
                                            </option>
                                        @endforeach

                                    </select>

                                    @error('reason')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror


                                    <div class="text-muted fs-8 mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Select the overall reason for reconciliation.
                                    </div>

                                </div>


                                {{-- Notes --}}
                                <div class="col-lg-4">

                                    <label class="form-label fw-semibold">
                                        General Notes
                                    </label>

                                    <div class="position-relative">

                                        <i
                                            class="bi bi-journal-text position-absolute top-50 translate-middle-y ms-4 text-muted"></i>

                                        <input type="text" name="notes" value="{{ old('notes') }}"
                                            class="form-control form-control-solid ps-12 @error('notes') is-invalid @enderror"
                                            placeholder="Optional notes...">

                                        @error('notes')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                    </div>


                                    <div class="text-muted fs-8 mt-2">
                                        Add any additional context about this adjustment.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ============================================= --}}
                    {{-- PRODUCT COUNTING --}}
                    {{-- ============================================= --}}
                    <div class="card border-0 shadow-sm">

                        <div class="card-header border-0 pt-8">

                            <div class="card-title">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-boxes text-info fs-3"></i>
                                    </div>
                                </div>


                                <div>

                                    <h3 class="fw-bold text-gray-900 mb-1">
                                        Physical Count
                                    </h3>

                                    <div class="text-muted fs-8">
                                        Enter the actual quantity counted for each product.
                                    </div>

                                </div>

                            </div>


                            <div class="card-toolbar">

                                <button type="button" id="add-adjustment-row" class="btn btn-light-primary btn-sm">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Add Product
                                </button>

                            </div>

                        </div>


                        <div class="card-body pt-4">

                            {{-- Info --}}
                            <div class="rounded-4 bg-light-warning p-5 mb-6">

                                <div class="d-flex align-items-start">

                                    <div class="symbol symbol-40px me-4 flex-shrink-0">
                                        <div class="symbol-label bg-white">
                                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                        </div>
                                    </div>


                                    <div>

                                        <div class="fw-bold text-gray-900 mb-1">
                                            Enter the physical quantity you actually counted
                                        </div>

                                        <div class="text-muted fs-8">
                                            The system can use this value to determine the difference
                                            between recorded stock and actual stock.
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <div class="table-responsive">

                                <table class="table align-middle table-row-dashed gy-5" id="adjustment-items-table">

                                    <thead>

                                        <tr class="text-muted fw-bold fs-8 text-uppercase">

                                            <th class="min-w-300px">
                                                Product
                                            </th>

                                            <th class="min-w-150px">
                                                Actual Qty
                                            </th>

                                            <th class="min-w-220px">
                                                Line Reason
                                            </th>

                                            <th class="text-end min-w-70px">
                                                Remove
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <tr class="adjustment-row">

                                            {{-- Product --}}
                                            <td>

                                                <select name="items[0][product_id]"
                                                    class="form-select form-select-solid product-select @error('items.0.product_id') is-invalid @enderror"
                                                    required>
                                                    <option value="">
                                                        Select product
                                                    </option>

                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            @selected((string) old('items.0.product_id') === (string) $product->id)>
                                                            {{ $product->name }}
                                                            @if ($product->sku)
                                                                — {{ $product->sku }}
                                                            @endif
                                                        </option>
                                                    @endforeach

                                                </select>

                                            </td>


                                            {{-- Actual Qty --}}
                                            <td>

                                                <div class="input-group">

                                                    <input type="number" min="0"
                                                        name="items[0][actual_quantity]"
                                                        value="{{ old('items.0.actual_quantity') }}"
                                                        class="form-control form-control-solid text-end"
                                                        placeholder="0" required>

                                                    <span class="input-group-text border-0 bg-light">
                                                        Qty
                                                    </span>

                                                </div>

                                            </td>


                                            {{-- Line Reason --}}
                                            <td>

                                                <div class="position-relative">

                                                    <i
                                                        class="bi bi-chat-left-text position-absolute top-50 translate-middle-y ms-4 text-muted"></i>

                                                    <input type="text" name="items[0][reason]"
                                                        value="{{ old('items.0.reason') }}"
                                                        class="form-control form-control-solid ps-12"
                                                        placeholder="Optional reason...">

                                                </div>

                                            </td>


                                            {{-- Delete --}}
                                            <td class="text-end">

                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-light-danger remove-row"
                                                    data-bs-toggle="tooltip" title="Remove Product">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>


                            {{-- Add another row --}}
                            <div class="d-flex justify-content-center border-top border-gray-200 pt-6 mt-3">

                                <button type="button" id="add-adjustment-row-bottom" class="btn btn-light-primary">
                                    <i class="bi bi-plus-lg me-2"></i>
                                    Add Another Product
                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- SIDEBAR --}}
                {{-- ================================================= --}}
                <div class="col-xl-3">

                    {{-- ============================================= --}}
                    {{-- WORKFLOW --}}
                    {{-- ============================================= --}}
                    <div class="card border-0 shadow-sm mb-8">

                        <div class="card-header border-0 pt-8">

                            <div class="card-title">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-list-check text-primary"></i>
                                    </div>
                                </div>

                                <div>

                                    <h3 class="fw-bold text-gray-900 mb-1">
                                        Adjustment Flow
                                    </h3>

                                    <div class="text-muted fs-8">
                                        How reconciliation works.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="card-body pt-4">

                            {{-- Step 1 --}}
                            <div class="d-flex align-items-start mb-6">

                                <div class="symbol symbol-40px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-light-primary fw-bold text-primary">
                                        1
                                    </div>
                                </div>


                                <div>

                                    <div class="fw-bold text-gray-900 mb-1">
                                        Select Branch
                                    </div>

                                    <div class="text-muted fs-8">
                                        Choose where the physical count was performed.
                                    </div>

                                </div>

                            </div>


                            <div class="separator separator-dashed mb-6"></div>


                            {{-- Step 2 --}}
                            <div class="d-flex align-items-start mb-6">

                                <div class="symbol symbol-40px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-light-info fw-bold text-info">
                                        2
                                    </div>
                                </div>


                                <div>

                                    <div class="fw-bold text-gray-900 mb-1">
                                        Count Products
                                    </div>

                                    <div class="text-muted fs-8">
                                        Enter the actual physical quantities.
                                    </div>

                                </div>

                            </div>


                            <div class="separator separator-dashed mb-6"></div>


                            {{-- Step 3 --}}
                            <div class="d-flex align-items-start">

                                <div class="symbol symbol-40px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-light-success fw-bold text-success">
                                        3
                                    </div>
                                </div>


                                <div>

                                    <div class="fw-bold text-gray-900 mb-1">
                                        Post Adjustment
                                    </div>

                                    <div class="text-muted fs-8">
                                        Save the reconciliation and update stock.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ============================================= --}}
                    {{-- SUMMARY --}}
                    {{-- ============================================= --}}
                    <div class="card border-0 shadow-sm mb-8">

                        <div class="card-header border-0 pt-8">

                            <div class="card-title">

                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-clipboard-data text-info"></i>
                                    </div>
                                </div>


                                <div>

                                    <h3 class="fw-bold text-gray-900 mb-1">
                                        Adjustment Summary
                                    </h3>

                                    <div class="text-muted fs-8">
                                        Current entry information.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="card-body pt-4">

                            <div class="d-flex justify-content-between align-items-center mb-5">

                                <span class="text-muted">
                                    Product Rows
                                </span>

                                <span id="adjustment-row-count" class="badge badge-light-primary px-3 py-2">
                                    1
                                </span>

                            </div>


                            <div class="separator separator-dashed mb-5"></div>


                            <div class="d-flex justify-content-between align-items-center">

                                <span class="text-muted">
                                    Status
                                </span>

                                <span class="badge badge-light-warning px-3 py-2">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    Draft
                                </span>

                            </div>

                        </div>

                    </div>


                    {{-- ============================================= --}}
                    {{-- FINAL ACTION --}}
                    {{-- ============================================= --}}
                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-6">

                            <div class="d-flex align-items-start mb-6">

                                <div class="symbol symbol-45px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-shield-check text-warning fs-3"></i>
                                    </div>
                                </div>


                                <div>

                                    <div class="fw-bold text-gray-900 mb-1">
                                        Review Before Posting
                                    </div>

                                    <div class="text-muted fs-8">
                                        Verify branch, product and physical quantities before
                                        saving the adjustment.
                                    </div>

                                </div>

                            </div>


                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-check2-circle me-2"></i>
                                Post Adjustment
                            </button>


                            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light w-100">
                                <i class="bi bi-x-lg me-2"></i>
                                Cancel
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>


    {{-- ============================================================= --}}
    {{-- ADJUSTMENT ROW SCRIPT --}}
    {{-- ============================================================= --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const tableBody =
                    document.querySelector('#adjustment-items-table tbody');

                const addButton =
                    document.getElementById('add-adjustment-row');

                const addBottomButton =
                    document.getElementById('add-adjustment-row-bottom');

                const rowCount =
                    document.getElementById('adjustment-row-count');

                let index =
                    tableBody ?
                    tableBody.querySelectorAll('tr').length :
                    1;


                /*
                |--------------------------------------------------------------------------
                | Update Row Count
                |--------------------------------------------------------------------------
                */
                function updateRowCount() {

                    if (!tableBody || !rowCount) {
                        return;
                    }

                    rowCount.textContent =
                        tableBody.querySelectorAll('tr').length;

                }


                /*
                |--------------------------------------------------------------------------
                | Add Adjustment Row
                |--------------------------------------------------------------------------
                */
                function addAdjustmentRow() {

                    if (!tableBody) {
                        return;
                    }

                    const firstRow =
                        tableBody.querySelector('tr');

                    if (!firstRow) {
                        return;
                    }

                    const clone =
                        firstRow.cloneNode(true);


                    clone.querySelectorAll('select, input').forEach(function(field) {

                        if (field.name) {

                            field.name =
                                field.name.replace(
                                    /items\[\d+\]/,
                                    'items[' + index + ']'
                                );

                        }

                        if (
                            field.tagName === 'SELECT' ||
                            field.type === 'text' ||
                            field.type === 'number'
                        ) {
                            field.value = '';
                        }

                    });


                    clone.querySelectorAll('.is-invalid').forEach(function(field) {
                        field.classList.remove('is-invalid');
                    });


                    clone.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
                        feedback.remove();
                    });


                    tableBody.appendChild(clone);

                    index++;

                    updateRowCount();

                }


                addButton?.addEventListener(
                    'click',
                    addAdjustmentRow
                );


                addBottomButton?.addEventListener(
                    'click',
                    addAdjustmentRow
                );


                /*
                |--------------------------------------------------------------------------
                | Remove Row
                |--------------------------------------------------------------------------
                */
                tableBody?.addEventListener('click', function(event) {

                    const removeButton =
                        event.target.closest('.remove-row');

                    if (!removeButton) {
                        return;
                    }


                    const rows =
                        tableBody.querySelectorAll('tr');


                    if (rows.length <= 1) {
                        return;
                    }


                    removeButton.closest('tr')?.remove();

                    updateRowCount();

                });


                updateRowCount();

            });
        </script>
    @endpush

</x-default-layout>
