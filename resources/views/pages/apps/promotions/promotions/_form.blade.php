@csrf
@php
    $selectedBranches = collect(old('branch_ids', $promotion->exists ? $promotion->branches->pluck('id')->all() : []))->map(fn($id) => (int) $id);
    $selectedServices = collect(old('service_ids', $promotion->exists ? $promotion->services->pluck('id')->all() : []))->map(fn($id) => (int) $id);
    $selectedProducts = collect(old('product_ids', $promotion->exists ? $promotion->products->pluck('id')->all() : []))->map(fn($id) => (int) $id);
    $selectedCustomers = collect(old('customer_ids', $promotion->exists ? $promotion->customers->pluck('id')->all() : []))->map(fn($id) => (int) $id);
    $selectedMemberships = collect(old('membership_plan_ids', $promotion->exists ? $promotion->membershipPlans->pluck('id')->all() : []))->map(fn($id) => (int) $id);
@endphp
@if ($isSuperAdmin)
    <div class="mb-6">
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
    <div class="col-md-8"><label class="form-label required">Promotion Name</label><input name="name" value="{{ old('name', $promotion->name) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-4"><label class="form-label required">Status</label><select name="status" class="form-select form-select-solid">@foreach (\App\Models\Promotion::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $promotion->status) === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label required">Discount Type</label><select name="discount_type" class="form-select form-select-solid">@foreach (\App\Models\Promotion::DISCOUNT_TYPES as $type)<option value="{{ $type }}" @selected(old('discount_type', $promotion->discount_type) === $type)>{{ str($type)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label required">Discount Value</label><input type="number" step="0.01" name="discount_value" value="{{ old('discount_value', $promotion->discount_value) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label">Maximum Discount</label><input type="number" step="0.01" name="maximum_discount_amount" value="{{ old('maximum_discount_amount', $promotion->maximum_discount_amount) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label required">Application</label><select name="application_type" class="form-select form-select-solid">@foreach (\App\Models\Promotion::APPLICATION_TYPES as $type)<option value="{{ $type }}" @selected(old('application_type', $promotion->application_type) === $type)>{{ str($type)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label required">Target</label><select name="target_scope" class="form-select form-select-solid">@foreach (\App\Models\Promotion::TARGET_SCOPES as $scope)<option value="{{ $scope }}" @selected(old('target_scope', $promotion->target_scope) === $scope)>{{ str($scope)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label required">Customer Scope</label><select name="customer_scope" class="form-select form-select-solid">@foreach (\App\Models\Promotion::CUSTOMER_SCOPES as $scope)<option value="{{ $scope }}" @selected(old('customer_scope', $promotion->customer_scope) === $scope)>{{ str($scope)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label required">Branch Scope</label><select name="branch_scope" class="form-select form-select-solid">@foreach (\App\Models\Promotion::BRANCH_SCOPES as $scope)<option value="{{ $scope }}" @selected(old('branch_scope', $promotion->branch_scope) === $scope)>{{ str($scope)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Priority</label><input type="number" name="priority" value="{{ old('priority', $promotion->priority) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label required">Starts At</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\\TH:i')) }}" class="form-control form-control-solid" required></div>
    <div class="col-md-3"><label class="form-label">Ends At</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\\TH:i')) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Minimum Spend</label><input type="number" step="0.01" name="minimum_spend" value="{{ old('minimum_spend', $promotion->minimum_spend) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Minimum Quantity</label><input type="number" name="minimum_quantity" value="{{ old('minimum_quantity', $promotion->minimum_quantity) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Usage Limit</label><input type="number" name="usage_limit" value="{{ old('usage_limit', $promotion->usage_limit) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Per-Customer Limit</label><input type="number" name="per_customer_limit" value="{{ old('per_customer_limit', $promotion->per_customer_limit) }}" class="form-control form-control-solid"></div>
    <div class="col-md-6"><label class="form-label">Branches</label><select name="branch_ids[]" multiple class="form-select form-select-solid">@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected($selectedBranches->contains($branch->id))>{{ $branch->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Services</label><select name="service_ids[]" multiple class="form-select form-select-solid">@foreach ($services as $service)<option value="{{ $service->id }}" @selected($selectedServices->contains($service->id))>{{ $service->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Products</label><select name="product_ids[]" multiple class="form-select form-select-solid">@foreach ($products as $product)<option value="{{ $product->id }}" @selected($selectedProducts->contains($product->id))>{{ $product->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Selected Customers</label><select name="customer_ids[]" multiple class="form-select form-select-solid">@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected($selectedCustomers->contains($customer->id))>{{ $customer->full_name }} {{ $customer->phone ? '(' . $customer->phone . ')' : '' }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Membership Plans</label><select name="membership_plan_ids[]" multiple class="form-select form-select-solid">@foreach ($membershipPlans as $plan)<option value="{{ $plan->id }}" @selected($selectedMemberships->contains($plan->id))>{{ $plan->name }}</option>@endforeach</select></div>
    <div class="col-md-6 d-flex align-items-end"><label class="form-check form-switch form-check-custom form-check-solid mb-3"><input type="checkbox" name="is_stackable" value="1" class="form-check-input" @checked(old('is_stackable', $promotion->is_stackable))><span class="form-check-label">Allow stacking with other discounts</span></label></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control form-control-solid">{{ old('description', $promotion->description) }}</textarea></div>
    <div class="col-12"><label class="form-label">Internal Notes</label><textarea name="internal_notes" rows="3" class="form-control form-control-solid">{{ old('internal_notes', $promotion->internal_notes) }}</textarea></div>
</div>
<div class="text-end mt-8"><button class="btn btn-primary">Save Promotion</button></div>
