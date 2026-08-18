@csrf
@if ($method ?? false)
    @method($method)
@endif

@if ($tenants->isNotEmpty())
    <div class="mb-7">
        <label class="form-label required fw-semibold">Salon</label>
        <select name="tenant_id" class="form-select form-select-solid" required>
            <option value="">Select salon</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="row g-6">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7">
                <h3 class="fw-bold mb-0">Basic Information</h3>
            </div>
            <div class="card-body">
                <div class="mb-6">
                    <label class="form-label required fw-semibold">Product Name</label>
                    <input name="name" value="{{ old('name', $product->name) }}" class="form-control form-control-solid" required>
                </div>
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select form-select-solid">
                            <option value="">Uncategorized</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Brand</label>
                        <select name="brand_id" class="form-select form-select-solid">
                            <option value="">No brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" rows="4" class="form-control form-control-solid">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-7">
                <h3 class="fw-bold mb-0">Inventory Settings</h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">Product Type</label>
                        <select name="product_type" class="form-select form-select-solid" required>
                            @foreach ($productTypes as $type)
                                <option value="{{ $type }}" @selected(old('product_type', $product->product_type) === $type)>{{ str($type)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Unit</label>
                        <select name="unit_id" class="form-select form-select-solid">
                            <option value="">No unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product->unit_id) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Reorder Level</label>
                        <input type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}" class="form-control form-control-solid">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Minimum Stock</label>
                        <input type="number" min="0" name="minimum_stock_level" value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? 0) }}" class="form-control form-control-solid">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch form-check-custom form-check-solid mt-10">
                            <input type="hidden" name="track_inventory" value="0">
                            <input class="form-check-input" type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory', $product->track_inventory ?? true))>
                            <label class="form-check-label fw-semibold">Track inventory</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch form-check-custom form-check-solid mt-10">
                            <input type="hidden" name="allow_negative_stock" value="0">
                            <input class="form-check-input" type="checkbox" name="allow_negative_stock" value="1" @checked(old('allow_negative_stock', $product->allow_negative_stock ?? false))>
                            <label class="form-check-label fw-semibold">Allow negative stock</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7">
                <h3 class="fw-bold mb-0">Identifiers</h3>
            </div>
            <div class="card-body">
                <div class="mb-6">
                    <label class="form-label required fw-semibold">SKU</label>
                    <input name="sku" value="{{ old('sku', $product->sku) }}" class="form-control form-control-solid" required>
                </div>
                <div>
                    <label class="form-label fw-semibold">Barcode</label>
                    <input name="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-control form-control-solid">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 pt-7">
                <h3 class="fw-bold mb-0">Pricing</h3>
            </div>
            <div class="card-body">
                <div class="mb-6">
                    <label class="form-label required fw-semibold">Cost Price</label>
                    <input type="number" min="0" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? 0) }}" class="form-control form-control-solid" required>
                </div>
                <div class="mb-6">
                    <label class="form-label required fw-semibold">Selling Price</label>
                    <input type="number" min="0" step="0.01" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? 0) }}" class="form-control form-control-solid" required>
                </div>
                <div>
                    <label class="form-label fw-semibold">Tax Rate</label>
                    <div class="input-group input-group-solid">
                        <input type="number" min="0" max="100" step="0.01" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate ?? 0) }}" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>

        @if (! $product->exists)
            <div class="card border-0 shadow-sm mb-8">
                <div class="card-header border-0 pt-7">
                    <h3 class="fw-bold mb-0">Opening Stock</h3>
                </div>
                <div class="card-body">
                    <div class="mb-6">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="opening_branch_id" class="form-select form-select-solid">
                            <option value="">No opening stock</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('opening_branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-6">
                        <label class="form-label fw-semibold">Opening Quantity</label>
                        <input type="number" min="0" name="opening_quantity" value="{{ old('opening_quantity', 0) }}" class="form-control form-control-solid">
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Opening Cost</label>
                        <input type="number" min="0" step="0.01" name="opening_cost" value="{{ old('opening_cost') }}" class="form-control form-control-solid">
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="form-check form-switch form-check-custom form-check-solid mb-7">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
                    <label class="form-check-label fw-semibold">Active product</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-check2-circle me-2"></i>{{ $submitLabel }}
                </button>
                <a href="{{ route('inventory.products.index') }}" class="btn btn-light w-100 mt-3">Cancel</a>
            </div>
        </div>
    </div>
</div>
