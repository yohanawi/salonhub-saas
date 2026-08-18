@csrf
@if ($method ?? false)
    @method($method)
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if ($tenants->isNotEmpty())
            <div class="mb-6">
                <label class="form-label required fw-semibold">Salon</label>
                <select name="tenant_id" class="form-select form-select-solid" required>
                    <option value="">Select salon</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="mb-6">
            <label class="form-label required fw-semibold">Brand Name</label>
            <input name="name" value="{{ old('name', $brand->name) }}" class="form-control form-control-solid" required>
        </div>
        <div class="mb-6">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" rows="4" class="form-control form-control-solid">{{ old('description', $brand->description) }}</textarea>
        </div>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active ?? true))>
            <label class="form-check-label fw-semibold">Active</label>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-3">
        <a href="{{ route('inventory.brands.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
