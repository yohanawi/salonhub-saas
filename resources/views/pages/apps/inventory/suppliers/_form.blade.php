@include('pages/apps.inventory.partials._alerts')

<div class="card border-0 shadow-sm">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="symbol symbol-45px me-4">
                <div class="symbol-label bg-light-primary"><i class="bi bi-truck text-primary fs-2"></i></div>
            </div>
            <div>
                <h3 class="fw-bold text-gray-900 mb-1">{{ $supplier->exists ? 'Edit Supplier' : 'New Supplier' }}</h3>
                <div class="text-muted fs-8">Contact details used on purchase orders.</div>
            </div>
        </div>
    </div>

    <div class="card-body">
        @if ($isSuperAdmin && ! $supplier->exists)
            <div class="mb-6">
                <label class="form-label required">Salon</label>
                <select name="tenant_id" class="form-select form-select-solid" required>
                    <option value="">Select salon</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="row g-6">
            <div class="col-md-6">
                <label class="form-label required">Supplier Name</label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-control form-control-solid @error('name') is-invalid @enderror" required maxlength="150">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control form-control-solid @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Tax / VAT Number</label>
                <input type="text" name="tax_number" value="{{ old('tax_number', $supplier->tax_number) }}" class="form-control form-control-solid">
            </div>
            <div class="col-md-6">
                <label class="form-label">Address</label>
                <textarea name="address" rows="3" class="form-control form-control-solid">{{ old('address', $supplier->address) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-control form-control-solid" placeholder="Payment terms, delivery days...">{{ old('notes', $supplier->notes) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))>
                    <span class="form-check-label fw-semibold">Active (available on new purchase orders)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-3">
        <a href="{{ route('inventory.suppliers.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $supplier->exists ? 'Save Changes' : 'Create Supplier' }}</button>
    </div>
</div>
