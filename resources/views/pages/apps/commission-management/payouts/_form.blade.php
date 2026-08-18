<form method="POST" action="{{ route('commission-management.payouts.store') }}">
    @csrf
    @if($isSuperAdmin)
        <div class="mb-6">
            <label class="form-label required">Salon</label>
            <select name="tenant_id" class="form-select form-select-solid" onchange="window.location='{{ route('commission-management.payouts.create') }}?tenant_id='+this.value">
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
            <select name="staff_id" class="form-select form-select-solid">
                <option value="">Select staff</option>
                @foreach($staffMembers as $member)
                    <option value="{{ $member->id }}" @selected((string) old('staff_id') === (string) $member->id)>{{ $member->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Branch</label>
            <select name="branch_id" class="form-select form-select-solid">
                <option value="">All branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label required">Period Start</label><input type="date" name="period_start" value="{{ old('period_start', $payout->period_start?->toDateString()) }}" class="form-control form-control-solid"></div>
        <div class="col-md-2"><label class="form-label required">Period End</label><input type="date" name="period_end" value="{{ old('period_end', $payout->period_end?->toDateString()) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Adjustment</label><input type="number" step="0.01" name="adjustment_amount" value="{{ old('adjustment_amount', 0) }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Payment Method</label><input type="text" name="payment_method" value="{{ old('payment_method') }}" class="form-control form-control-solid"></div>
        <div class="col-md-4"><label class="form-label">Reference</label><input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="form-control form-control-solid"></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control form-control-solid" rows="3">{{ old('notes') }}</textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-3 mt-8">
        <a href="{{ route('commission-management.payouts.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary" @disabled($isSuperAdmin && ! $selectedTenant)>Create Payout</button>
    </div>
</form>
