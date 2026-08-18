@if($isSuperAdmin && ! $selectedTenant)
    <div class="alert alert-info border-0 shadow-sm">Select a salon before creating a commission rule.</div>
@endif

<form method="POST" action="{{ $rule->exists ? route('commission-management.rules.update', $rule) : route('commission-management.rules.store') }}">
    @csrf
    @if($rule->exists)
        @method('PUT')
    @endif
    @if($isSuperAdmin)
        <div class="mb-6">
            <label class="form-label required">Salon</label>
            <select name="tenant_id" class="form-select form-select-solid" onchange="if(!{{ $rule->exists ? 'true' : 'false' }}) window.location='{{ route('commission-management.rules.create') }}?tenant_id='+this.value" @disabled($rule->exists)>
                <option value="">Select salon</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="row g-6">
        <div class="col-md-4">
            <label class="form-label required">Apply To</label>
            <select name="commission_scope" class="form-select form-select-solid">
                @foreach(\App\Models\CommissionRule::SCOPES as $scope)
                    <option value="{{ $scope }}" @selected(old('commission_scope', $rule->commission_scope) === $scope)>{{ str($scope)->replace('_', ' + ')->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Branch</label>
            <select name="branch_id" class="form-select form-select-solid">
                <option value="">None</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $rule->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Staff</label>
            <select name="staff_id" class="form-select form-select-solid">
                <option value="">None</option>
                @foreach($staffMembers as $member)
                    <option value="{{ $member->id }}" @selected((string) old('staff_id', $rule->staff_id) === (string) $member->id)>{{ $member->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Service</label>
            <select name="service_id" class="form-select form-select-solid">
                <option value="">None</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" @selected((string) old('service_id', $rule->service_id) === (string) $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-select form-select-solid">
                <option value="">None</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id', $rule->product_id) === (string) $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label required">Commission Type</label>
            <select name="commission_type" class="form-select form-select-solid">
                @foreach(\App\Models\CommissionSetting::TYPES as $type)
                    <option value="{{ $type }}" @selected(old('commission_type', $rule->commission_type) === $type)>{{ str($type)->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label required">Commission Value</label>
            <input type="number" step="0.01" min="0" name="commission_value" value="{{ old('commission_value', $rule->commission_value) }}" class="form-control form-control-solid">
        </div>
        <div class="col-md-4">
            <label class="form-label required">Calculation Basis</label>
            <select name="calculate_on" class="form-select form-select-solid">
                @foreach(\App\Models\CommissionSetting::BASES as $basis)
                    <option value="{{ $basis }}" @selected(old('calculate_on', $rule->calculate_on) === $basis)>{{ str($basis)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Priority</label>
            <input type="number" name="priority" value="{{ old('priority', $rule->priority) }}" class="form-control form-control-solid">
        </div>
        <div class="col-md-4">
            <label class="form-label">Effective From</label>
            <input type="date" name="effective_from" value="{{ old('effective_from', optional($rule->effective_from)->toDateString()) }}" class="form-control form-control-solid">
        </div>
        <div class="col-md-4">
            <label class="form-label">Effective Until</label>
            <input type="date" name="effective_to" value="{{ old('effective_to', optional($rule->effective_to)->toDateString()) }}" class="form-control form-control-solid">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <label class="form-check form-switch form-check-custom form-check-solid">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->is_active ?? true))>
                <span class="form-check-label fw-semibold">Active</span>
            </label>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-3 mt-8">
        <a href="{{ route('commission-management.rules.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary" @disabled($isSuperAdmin && ! $selectedTenant)>Save Rule</button>
    </div>
</form>
