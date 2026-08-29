@csrf

@if ($method ?? false)
    @method($method)
@endif


{{-- ================================================================ --}}
{{-- SALON / TENANT --}}
{{-- ================================================================ --}}
@if ($tenants->isNotEmpty())

    <div class="card border-0 shadow-sm mb-8">

        <div class="card-body p-6">

            <div class="rounded-4 bg-light-primary p-5">

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-5">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-white">
                                <i class="bi bi-shop text-primary fs-3"></i>
                            </div>
                        </div>

                        <div>
                            <h4 class="fw-bold text-gray-900 mb-1">
                                Product Salon
                            </h4>

                            <div class="text-muted fs-8">
                                Choose the salon that owns and manages this inventory product.
                            </div>
                        </div>

                    </div>


                    <div class="flex-grow-1 flex-md-grow-0">

                        <select name="tenant_id"
                            class="w-400px form-select bg-white @error('tenant_id') is-invalid @enderror"
                            data-control="select2" data-hide-search="true" required>
                            <option value="">
                                Select salon
                            </option>

                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>
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

                </div>

            </div>

        </div>

    </div>

@endif


<div class="row g-8">

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">


        {{-- ======================================================== --}}
        {{-- BASIC INFORMATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-box-seam text-primary fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Product Information
                        </h3>

                        <div class="text-muted fs-8">
                            Define the product identity, classification and description.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- Product Name --}}
                    <div class="col-12">

                        <label class="form-label required fw-semibold">
                            Product Name
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-tag text-muted"></i>
                            </span>

                            <input type="text" name="name" id="product_name"
                                value="{{ old('name', $product->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="e.g. Argan Oil Shampoo" required>

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Category --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Category
                        </label>

                        <select name="category_id"
                            class="form-select form-select-solid @error('category_id') is-invalid @enderror"
                            data-control="select2" data-hide-search="true">
                            <option value="">
                                Uncategorized
                            </option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('category_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Brand --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Brand
                        </label>

                        <select name="brand_id"
                            class="form-select form-select-solid @error('brand_id') is-invalid @enderror"
                            data-control="select2" data-hide-search="true">
                            <option value="">
                                No brand
                            </option>

                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('brand_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Description --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Description
                        </label>

                        <textarea name="description" rows="5"
                            class="form-control form-control-solid @error('description') is-invalid @enderror"
                            placeholder="Add product details, usage notes or internal inventory information...">{{ old('description', $product->description) }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="text-muted fs-8 mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Keep this concise so staff can quickly identify the product.
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- INVENTORY CONFIGURATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-boxes text-info fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Inventory Configuration
                        </h3>

                        <div class="text-muted fs-8">
                            Configure stock tracking rules, units and reorder thresholds.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- Product Type --}}
                    <div class="col-md-4">

                        <label class="form-label required fw-semibold">
                            Product Type
                        </label>

                        <select name="product_type" id="product_type"
                            class="form-select form-select-solid @error('product_type') is-invalid @enderror"
                            data-control="select2" data-hide-search="true" required>

                            @foreach ($productTypes as $type)
                                <option value="{{ $type }}" @selected(old('product_type', $product->product_type) === $type)>
                                    {{ str($type)->headline() }}
                                </option>
                            @endforeach

                        </select>

                        @error('product_type')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Unit --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Stock Unit
                        </label>

                        <select name="unit_id"
                            class="form-select form-select-solid @error('unit_id') is-invalid @enderror"
                            data-control="select2" data-hide-search="true">

                            <option value="">
                                No unit
                            </option>

                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product->unit_id) === (string) $unit->id)>
                                    {{ $unit->name }} ({{ $unit->symbol }})
                                </option>
                            @endforeach

                        </select>

                        @error('unit_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Reorder --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Reorder Level
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-bell text-muted"></i>
                            </span>

                            <input type="number" min="0" name="reorder_level"
                                value="{{ old('reorder_level', $product->reorder_level ?? 0) }}"
                                class="form-control @error('reorder_level') is-invalid @enderror">

                            @error('reorder_level')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="text-muted fs-8 mt-2">
                            Low-stock warning threshold.
                        </div>

                    </div>


                    {{-- Minimum Stock --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Minimum Stock Level
                        </label>

                        <input type="number" min="0" name="minimum_stock_level"
                            value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? 0) }}"
                            class="form-control form-control-solid @error('minimum_stock_level') is-invalid @enderror">

                        @error('minimum_stock_level')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                <div class="separator separator-dashed my-7"></div>


                {{-- Inventory Toggles --}}
                <div class="row g-5">

                    {{-- Track Inventory --}}
                    <div class="col-md-6">

                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 h-100">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-graph-up-arrow text-success fs-3"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="fw-bold text-gray-900 mb-1">
                                        Track Inventory
                                    </div>

                                    <div class="text-muted fs-8">
                                        Maintain quantity-on-hand and stock movement history.
                                    </div>
                                </div>

                            </div>


                            <div class="form-check form-switch form-check-custom form-check-solid ms-3">

                                <input type="hidden" name="track_inventory" value="0">

                                <input class="form-check-input" type="checkbox" name="track_inventory"
                                    value="1" @checked(old('track_inventory', $product->track_inventory ?? true))>

                            </div>

                        </label>

                    </div>


                    {{-- Negative Stock --}}
                    <div class="col-md-6">

                        <label
                            class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 h-100">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-45px me-4">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-exclamation-triangle text-warning fs-3"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="fw-bold text-gray-900 mb-1">
                                        Allow Negative Stock
                                    </div>

                                    <div class="text-muted fs-8">
                                        Permit transactions when available stock reaches zero.
                                    </div>
                                </div>

                            </div>


                            <div class="form-check form-switch form-check-custom form-check-solid ms-3">

                                <input type="hidden" name="allow_negative_stock" value="0">

                                <input class="form-check-input" type="checkbox" name="allow_negative_stock"
                                    value="1" @checked(old('allow_negative_stock', $product->allow_negative_stock ?? false))>

                            </div>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- OPENING STOCK --}}
        {{-- ======================================================== --}}
        @if (!$product->exists)

            <div class="card border-0 shadow-sm">

                <div class="card-header border-0 pt-8">

                    <div class="card-title">

                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-box-arrow-in-down text-success fs-3"></i>
                            </div>
                        </div>

                        <div>
                            <h3 class="fw-bold text-gray-900 mb-1">
                                Opening Stock
                            </h3>

                            <div class="text-muted fs-8">
                                Optionally register starting inventory when creating the product.
                            </div>
                        </div>

                    </div>


                    <div class="card-toolbar">

                        <span class="badge badge-light-success px-3 py-2">
                            Optional
                        </span>

                    </div>

                </div>


                <div class="card-body pt-4">

                    <div class="rounded-4 bg-light-success p-5 mb-6">

                        <div class="d-flex align-items-center">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-white">
                                    <i class="bi bi-info-circle text-success"></i>
                                </div>
                            </div>

                            <div class="text-gray-700 fs-8">
                                Opening stock creates the product's initial inventory balance
                                for the selected branch.
                            </div>

                        </div>

                    </div>


                    <div class="row g-5">

                        {{-- Branch --}}
                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Opening Branch
                            </label>

                            <select name="opening_branch_id"
                                class="form-select form-select-solid @error('opening_branch_id') is-invalid @enderror"
                                data-control="select2" data-hide-search="true">

                                <option value="">
                                    No opening stock
                                </option>

                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('opening_branch_id') === (string) $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach

                            </select>

                            @error('opening_branch_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Quantity --}}
                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Opening Quantity
                            </label>

                            <input type="number" min="0" name="opening_quantity"
                                value="{{ old('opening_quantity', 0) }}"
                                class="form-control form-control-solid @error('opening_quantity') is-invalid @enderror">

                            @error('opening_quantity')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Cost --}}
                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Opening Unit Cost
                            </label>

                            <div class="input-group">

                                <span class="input-group-text border-0 bg-light">
                                    LKR
                                </span>

                                <input type="number" min="0" step="0.01" name="opening_cost"
                                    value="{{ old('opening_cost') }}"
                                    class="form-control @error('opening_cost') is-invalid @enderror">

                                @error('opening_cost')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        @endif

    </div>


    {{-- ============================================================ --}}
    {{-- SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">


        {{-- ======================================================== --}}
        {{-- PRODUCT PREVIEW --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-body text-center p-7">

                <div class="symbol symbol-80px mb-5">
                    <div class="symbol-label bg-light-primary rounded-4">
                        <i class="bi bi-box-seam-fill text-primary fs-1"></i>
                    </div>
                </div>


                <h3 id="product_preview_name" class="fw-bold text-gray-900 mb-2">
                    {{ old('name', $product->name ?: 'New Product') }}
                </h3>


                <div class="text-muted fs-8 mb-5">
                    Inventory Product
                </div>


                <div class="d-flex justify-content-center gap-2">

                    <span class="badge badge-light-primary">
                        Catalog Item
                    </span>

                    <span id="product_preview_status"
                        class="badge badge-light-{{ old('is_active', $product->is_active ?? true) ? 'success' : 'secondary' }}">
                        {{ old('is_active', $product->is_active ?? true) ? 'Active' : 'Inactive' }}
                    </span>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- IDENTIFIERS --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-upc-scan text-primary"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Identifiers
                        </h3>

                        <div class="text-muted fs-8">
                            Internal and scannable product codes.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- SKU --}}
                <div class="mb-6">

                    <label class="form-label required fw-semibold">
                        SKU
                    </label>

                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-hash text-muted"></i>
                        </span>

                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                            class="form-control @error('sku') is-invalid @enderror" placeholder="PRD-0001" required>

                        @error('sku')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Barcode --}}
                <div>

                    <label class="form-label fw-semibold">
                        Barcode
                    </label>

                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-upc text-muted"></i>
                        </span>

                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                            class="form-control @error('barcode') is-invalid @enderror"
                            placeholder="Optional barcode">

                        @error('barcode')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- PRICING --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-cash-coin text-success"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Pricing
                        </h3>

                        <div class="text-muted fs-8">
                            Cost, selling price and tax configuration.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- Cost --}}
                <div class="mb-6">

                    <label class="form-label required fw-semibold">
                        Cost Price
                    </label>

                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            LKR
                        </span>

                        <input type="number" min="0" step="0.01" name="cost_price" id="cost_price"
                            value="{{ old('cost_price', $product->cost_price ?? 0) }}"
                            class="form-control @error('cost_price') is-invalid @enderror" required>

                        @error('cost_price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Selling --}}
                <div class="mb-6">

                    <label class="form-label required fw-semibold">
                        Selling Price
                    </label>

                    <div class="input-group">

                        <span class="input-group-text border-0 bg-light">
                            LKR
                        </span>

                        <input type="number" min="0" step="0.01" name="selling_price" id="selling_price"
                            value="{{ old('selling_price', $product->selling_price ?? 0) }}"
                            class="form-control @error('selling_price') is-invalid @enderror" required>

                        @error('selling_price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Tax --}}
                <div class="mb-6">

                    <label class="form-label fw-semibold">
                        Tax Rate
                    </label>

                    <div class="input-group">

                        <input type="number" min="0" max="100" step="0.01" name="tax_rate"
                            value="{{ old('tax_rate', $product->tax_rate ?? 0) }}"
                            class="form-control @error('tax_rate') is-invalid @enderror">

                        <span class="input-group-text border-0 bg-light">
                            %
                        </span>

                        @error('tax_rate')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Margin Preview --}}
                <div class="rounded-4 bg-light-success p-5">

                    <div class="d-flex align-items-center justify-content-between">

                        <div>

                            <div class="text-muted fs-8 mb-1">
                                Estimated Margin
                            </div>

                            <div id="margin_preview" class="fw-bolder text-success fs-4">
                                LKR 0.00
                            </div>

                        </div>


                        <div class="symbol symbol-40px">

                            <div class="symbol-label bg-white">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- PRODUCT STATUS / ACTIONS --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-sliders text-success"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Product Status
                        </h3>

                        <div class="text-muted fs-8">
                            Control product availability in your catalog.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <label
                    class="d-flex align-items-center justify-content-between rounded-4 border border-gray-300 p-5 mb-7">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-toggle-on text-success"></i>
                            </div>
                        </div>

                        <div>

                            <div class="fw-bold text-gray-900 mb-1">
                                Active Product
                            </div>

                            <div class="text-muted fs-8">
                                Make this product available for inventory operations.
                            </div>

                        </div>

                    </div>


                    <div class="form-check form-switch form-check-custom form-check-solid ms-3">

                        <input type="hidden" name="is_active" value="0">

                        <input id="product_status" class="form-check-input" type="checkbox" name="is_active"
                            value="1" @checked(old('is_active', $product->is_active ?? true))>

                    </div>

                </label>


                <div class="d-grid gap-3">

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check2-circle me-2"></i>
                        {{ $submitLabel }}
                    </button>


                    <a href="{{ route('inventory.products.index') }}" class="btn btn-light">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancel
                    </a>

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

            /*
            |--------------------------------------------------------------------------
            | Product Preview
            |--------------------------------------------------------------------------
            */

            const productName =
                document.getElementById('product_name');

            const previewName =
                document.getElementById('product_preview_name');

            productName?.addEventListener('input', function() {

                if (!previewName) {
                    return;
                }

                previewName.textContent =
                    this.value.trim() || 'New Product';

            });


            /*
            |--------------------------------------------------------------------------
            | Active Status Preview
            |--------------------------------------------------------------------------
            */

            const productStatus =
                document.getElementById('product_status');

            const previewStatus =
                document.getElementById('product_preview_status');


            function updateProductStatusPreview() {

                if (!productStatus || !previewStatus) {
                    return;
                }

                const active = productStatus.checked;

                previewStatus.textContent =
                    active ? 'Active' : 'Inactive';

                previewStatus.classList.toggle(
                    'badge-light-success',
                    active
                );

                previewStatus.classList.toggle(
                    'badge-light-secondary',
                    !active
                );

            }


            productStatus?.addEventListener(
                'change',
                updateProductStatusPreview
            );

            updateProductStatusPreview();


            /*
            |--------------------------------------------------------------------------
            | Estimated Margin
            |--------------------------------------------------------------------------
            */

            const costInput =
                document.getElementById('cost_price');

            const sellingInput =
                document.getElementById('selling_price');

            const marginPreview =
                document.getElementById('margin_preview');


            function updateMarginPreview() {

                if (!marginPreview) {
                    return;
                }

                const cost =
                    parseFloat(costInput?.value || 0);

                const selling =
                    parseFloat(sellingInput?.value || 0);

                const margin =
                    selling - cost;

                marginPreview.textContent =
                    `LKR ${margin.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;

                marginPreview.classList.toggle(
                    'text-success',
                    margin >= 0
                );

                marginPreview.classList.toggle(
                    'text-danger',
                    margin < 0
                );

            }


            costInput?.addEventListener(
                'input',
                updateMarginPreview
            );

            sellingInput?.addEventListener(
                'input',
                updateMarginPreview
            );

            updateMarginPreview();

        });
    </script>
@endpush
