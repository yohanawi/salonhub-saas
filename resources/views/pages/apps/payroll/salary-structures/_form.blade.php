<form method="POST" action="{{ $structure->exists ? route('payroll.salary-structures.update', $structure) : route('payroll.salary-structures.store') }}">
    @csrf
    @if($structure->exists) @method('PUT') @endif
    @if($isSuperAdmin)
        <div class="mb-6">
            <label class="form-label required">Salon</label>
            <select name="tenant_id" class="form-select form-select-solid" onchange="if(!{{ $structure->exists ? 'true' : 'false' }}) window.location='{{ route('payroll.salary-structures.create') }}?tenant_id='+this.value" @disabled($structure->exists)>
                <option value="">Select salon</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="row g-6">
        <div class="col-md-4">
            <label class="form-label required">Staff</label>
            <select name="staff_id" class="form-select form-select-solid" @disabled($structure->exists)>
                <option value="">Select staff</option>
                @foreach($staffMembers as $member)
                    <option value="{{ $member->id }}" @selected((string) old('staff_id', $structure->staff_id) === (string) $member->id)>{{ $member->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Branch</label><select name="branch_id" class="form-select form-select-solid"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $structure->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label required">Salary Type</label><select name="salary_type" class="form-select form-select-solid">@foreach(\App\Models\StaffSalaryStructure::TYPES as $type)<option value="{{ $type }}" @selected(old('salary_type', $structure->salary_type) === $type)>{{ str($type)->replace('_', ' ')->headline() }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Basic Salary</label><input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary', $structure->basic_salary) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Daily Rate</label><input type="number" step="0.01" name="daily_rate" value="{{ old('daily_rate', $structure->daily_rate) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Hourly Rate</label><input type="number" step="0.01" name="hourly_rate" value="{{ old('hourly_rate', $structure->hourly_rate) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Overtime Rate</label><input type="number" step="0.01" name="overtime_rate" value="{{ old('overtime_rate', $structure->overtime_rate) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label required">Effective From</label><input type="date" name="effective_from" value="{{ old('effective_from', optional($structure->effective_from)->toDateString()) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Effective To</label><input type="date" name="effective_to" value="{{ old('effective_to', optional($structure->effective_to)->toDateString()) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Payment Method</label><input type="text" name="payment_method" value="{{ old('payment_method', $structure->payment_method) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Bank Name</label><input type="text" name="bank_name" value="{{ old('bank_name', $structure->bank_name) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Account Number</label><input type="text" name="bank_account_number" value="{{ old('bank_account_number', $structure->bank_account_number) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4 d-flex align-items-end"><label class="form-check form-switch form-check-custom form-check-solid"><input type="hidden" name="commission_enabled" value="0"><input class="form-check-input" type="checkbox" name="commission_enabled" value="1" @checked(old('commission_enabled', $structure->commission_enabled ?? true))><span class="form-check-label">Include Commission</span></label></div>
        <div class="col-md-4 d-flex align-items-end"><label class="form-check form-switch form-check-custom form-check-solid"><input type="hidden" name="overtime_enabled" value="0"><input class="form-check-input" type="checkbox" name="overtime_enabled" value="1" @checked(old('overtime_enabled', $structure->overtime_enabled ?? false))><span class="form-check-label">Overtime Enabled</span></label></div>
        <input type="hidden" name="payroll_frequency" value="monthly">
        <input type="hidden" name="status" value="active">
    </div>
    <div class="d-flex justify-content-end gap-3 mt-8">
        <a href="{{ route('payroll.salary-structures.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary" @disabled($isSuperAdmin && ! $selectedTenant)>Save Salary Structure</button>
    </div>
</form>
