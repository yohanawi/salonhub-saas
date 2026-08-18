@csrf
@if ($isSuperAdmin)
    <div class="mb-5"><label class="form-label required">Tenant</label><select name="tenant_id" class="form-select form-select-solid" required><option value="">Select tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenant?->id) == $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>
@endif
<div class="row g-6">
    <div class="col-md-4"><label class="form-label required">Plan Name</label><input name="name" value="{{ old('name', $plan->name) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-2"><label class="form-label required">Code</label><input name="code" value="{{ old('code', $plan->code) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label required">Price</label><input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label">Joining Fee</label><input type="number" step="0.01" name="joining_fee" value="{{ old('joining_fee', $plan->joining_fee) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label required">Duration Type</label><select name="duration_type" class="form-select form-select-solid"><option value="days" @selected(old('duration_type', $plan->duration_type) === 'days')>Days</option><option value="months" @selected(old('duration_type', $plan->duration_type) === 'months')>Months</option><option value="years" @selected(old('duration_type', $plan->duration_type) === 'years')>Years</option></select></div>
    <div class="col-md-3"><label class="form-label required">Duration</label><input type="number" name="duration_value" value="{{ old('duration_value', $plan->duration_value) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label required">Billing</label><select name="billing_type" class="form-select form-select-solid"><option value="one_time" @selected(old('billing_type', $plan->billing_type) === 'one_time')>One Time</option><option value="recurring" @selected(old('billing_type', $plan->billing_type) === 'recurring')>Recurring</option></select></div>
    <div class="col-md-3"><label class="form-label required">Status</label><select name="status" class="form-select form-select-solid"><option value="active" @selected(old('status', $plan->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $plan->status) === 'inactive')>Inactive</option></select></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control form-control-solid" rows="3">{{ old('description', $plan->description) }}</textarea></div>
    <div class="col-12"><label class="form-check form-switch form-check-custom form-check-solid"><input type="checkbox" name="is_featured" value="1" class="form-check-input" @checked(old('is_featured', $plan->is_featured))><span class="form-check-label">Featured plan</span></label></div>
</div>

<div class="separator separator-dashed my-8"></div>
<h4 class="fw-bold mb-5">Benefits</h4>
@php($existingBenefits = old('benefits', $plan->benefits?->toArray() ?: [['benefit_type' => 'service_discount', 'discount_type' => 'percentage', 'discount_value' => 10, 'priority' => 100, 'status' => 'active']]))
@for ($i = 0; $i < max(3, count($existingBenefits)); $i++)
    @php($benefit = $existingBenefits[$i] ?? [])
    <div class="row g-4 mb-4">
        <div class="col-md-3"><select name="benefits[{{ $i }}][benefit_type]" class="form-select form-select-solid"><option value="">No benefit</option>@foreach(['service_discount' => 'Service Discount', 'product_discount' => 'Product Discount', 'free_service' => 'Free Service', 'bonus_points_multiplier' => 'Points Multiplier'] as $value => $label)<option value="{{ $value }}" @selected(($benefit['benefit_type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="benefits[{{ $i }}][discount_type]" class="form-select form-select-solid"><option value="percentage" @selected(($benefit['discount_type'] ?? '') === 'percentage')>Percentage</option><option value="fixed" @selected(($benefit['discount_type'] ?? '') === 'fixed')>Fixed</option></select></div>
        <div class="col-md-2"><input type="number" step="0.01" name="benefits[{{ $i }}][discount_value]" value="{{ $benefit['discount_value'] ?? 0 }}" class="form-control form-control-solid" placeholder="Discount"></div>
        <div class="col-md-2"><input type="number" step="0.01" name="benefits[{{ $i }}][loyalty_multiplier]" value="{{ $benefit['loyalty_multiplier'] ?? '' }}" class="form-control form-control-solid" placeholder="Multiplier"></div>
        <div class="col-md-2"><select name="benefits[{{ $i }}][service_id]" class="form-select form-select-solid"><option value="">Any service</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(($benefit['service_id'] ?? null) == $service->id)>{{ $service->name }}</option>@endforeach</select></div>
        <div class="col-md-1"><input type="number" name="benefits[{{ $i }}][priority]" value="{{ $benefit['priority'] ?? 100 }}" class="form-control form-control-solid"></div>
        <input type="hidden" name="benefits[{{ $i }}][status]" value="{{ $benefit['status'] ?? 'active' }}">
    </div>
@endfor
<div class="text-end mt-8"><button class="btn btn-primary">Save Plan</button></div>
