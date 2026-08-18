@csrf
@if ($isSuperAdmin)
    <div class="mb-5">
        <label class="form-label required">Tenant</label>
        <select name="tenant_id" class="form-select form-select-solid" required>
            <option value="">Select tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenant?->id) == $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
    </div>
@endif
<div class="row g-6">
    <div class="col-md-6"><label class="form-label required">Program Name</label><input name="name" value="{{ old('name', $program->name) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label required">Status</label><select name="status" class="form-select form-select-solid"><option value="active" @selected(old('status', $program->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $program->status) === 'inactive')>Inactive</option></select></div>
    <div class="col-md-3"><label class="form-label">Expiry Days</label><input type="number" name="points_expiry_days" value="{{ old('points_expiry_days', $program->points_expiry_days) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label required">Redeem Points</label><input type="number" name="redemption_points" value="{{ old('redemption_points', $program->redemption_points) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label required">Redeem Value</label><input type="number" step="0.01" name="redemption_value" value="{{ old('redemption_value', $program->redemption_value) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label">Minimum Points</label><input type="number" name="minimum_redeem_points" value="{{ old('minimum_redeem_points', $program->minimum_redeem_points) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Max Redeem %</label><input type="number" step="0.01" name="maximum_redeem_percentage" value="{{ old('maximum_redeem_percentage', $program->maximum_redeem_percentage) }}" class="form-control form-control-solid"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control form-control-solid">{{ old('description', $program->description) }}</textarea></div>
    <div class="col-md-6"><label class="form-check form-switch form-check-custom form-check-solid"><input type="checkbox" name="allow_partial_redemption" value="1" class="form-check-input" @checked(old('allow_partial_redemption', $program->allow_partial_redemption))><span class="form-check-label">Allow partial redemption</span></label></div>
    <div class="col-md-6"><label class="form-check form-switch form-check-custom form-check-solid"><input type="checkbox" name="allow_points_on_discounted_sales" value="1" class="form-check-input" @checked(old('allow_points_on_discounted_sales', $program->allow_points_on_discounted_sales))><span class="form-check-label">Allow points on discounted sales</span></label></div>
</div>
<div class="text-end mt-8"><button class="btn btn-primary">Save Program</button></div>
