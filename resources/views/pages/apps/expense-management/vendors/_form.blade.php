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
                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="row g-5">
            <div class="col-md-6">
                <label class="form-label required fw-semibold">Vendor Name</label>
                <input name="name" value="{{ old('name', $vendor->name) }}" class="form-control form-control-solid" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Company Name</label>
                <input name="company_name" value="{{ old('company_name', $vendor->company_name) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input name="phone" value="{{ old('phone', $vendor->phone) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tax Number</label>
                <input name="tax_number" value="{{ old('tax_number', $vendor->tax_number) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-6">
                <label class="form-label">Address</label>
                <textarea name="address" rows="4" class="form-control form-control-solid">{{ old('address', $vendor->address) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="4" class="form-control form-control-solid">{{ old('notes', $vendor->notes) }}</textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $vendor->is_active ?? true))>
                    <label class="form-check-label fw-semibold">Active</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-3">
        <a href="{{ route('expense-management.vendors.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
