@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-8">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-8">
        <div class="row g-6">
            @if (($tenants ?? collect())->isNotEmpty())
                <div class="col-12">
                    <label class="form-label required fw-semibold">Salon / Tenant</label>
                    <select name="tenant_id" class="form-select form-select-solid" data-control="select2" required>
                        <option value="">Select salon</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-lg-6">
                <label class="form-label required fw-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name', $paymentMethod->name) }}" class="form-control form-control-solid" required>
            </div>
            <div class="col-lg-6">
                <label class="form-label required fw-semibold">Code</label>
                <input type="text" name="code" value="{{ old('code', $paymentMethod->code) }}" class="form-control form-control-solid" required>
            </div>
            <div class="col-lg-6">
                <label class="form-label required fw-semibold">Type</label>
                <select name="type" class="form-select form-select-solid" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $paymentMethod->type) === $type)>{{ str($type)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label fw-semibold">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $paymentMethod->sort_order ?? 0) }}" class="form-control form-control-solid" min="0">
            </div>
            <div class="col-lg-6">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $paymentMethod->is_active ?? true))>
                    <span class="form-check-label fw-semibold">Active</span>
                </label>
            </div>
            <div class="col-lg-6">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="requires_reference" value="1" @checked(old('requires_reference', $paymentMethod->requires_reference ?? false))>
                    <span class="form-check-label fw-semibold">Requires Reference</span>
                </label>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-3 mt-8">
            <a href="{{ route('billing.payment-methods.index') }}" class="btn btn-light">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Payment Method</button>
        </div>
    </div>
</div>
