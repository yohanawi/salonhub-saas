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
        <div class="row g-5">
            <div class="col-md-5">
                <label class="form-label required fw-semibold">Name</label>
                <input name="name" value="{{ old('name', $unit->name) }}" class="form-control form-control-solid" required>
            </div>
            <div class="col-md-3">
                <label class="form-label required fw-semibold">Symbol</label>
                <input name="symbol" value="{{ old('symbol', $unit->symbol) }}" class="form-control form-control-solid" required>
            </div>
            <div class="col-md-4">
                <label class="form-label required fw-semibold">Type</label>
                <select name="type" class="form-select form-select-solid" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $unit->type) === $type)>{{ str($type)->headline() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-check form-switch form-check-custom form-check-solid mt-7">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $unit->is_active ?? true))>
            <label class="form-check-label fw-semibold">Active</label>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-3">
        <a href="{{ route('inventory.units.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
