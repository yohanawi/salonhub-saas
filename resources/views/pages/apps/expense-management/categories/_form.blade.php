@csrf
@if ($method ?? false) @method($method) @endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if ($tenants->isNotEmpty())
            <div class="mb-6">
                <label class="form-label required fw-semibold">Salon</label>
                <select name="tenant_id" class="form-select form-select-solid" required>
                    <option value="">Select salon</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="row g-5">
            <div class="col-md-6">
                <label class="form-label">Parent</label>
                <select name="parent_id" class="form-select form-select-solid">
                    <option value="">Main category</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label required fw-semibold">Name</label>
                <input name="name" value="{{ old('name', $category->name) }}" class="form-control form-control-solid" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Code</label>
                <input name="code" value="{{ old('code', $category->code) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-4">
                <label class="form-label">Color</label>
                <input name="color" value="{{ old('color', $category->color) }}" class="form-control form-control-solid" placeholder="#009EF7">
            </div>
            <div class="col-md-4">
                <label class="form-label">Icon</label>
                <input name="icon" value="{{ old('icon', $category->icon) }}" class="form-control form-control-solid" placeholder="bi-lightning">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-control form-control-solid">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
                    <label class="form-check-label fw-semibold">Active</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-3">
        <a href="{{ route('expense-management.categories.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
