<x-default-layout>
    @section('title') Commission Settings @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.settings.edit') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        @if($isSuperAdmin)
            <form method="GET" class="mb-8 w-300px">
                <select name="tenant_id" class="form-select form-select-solid" onchange="this.form.submit()">
                    <option value="">Select salon</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        @if($setting)
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Commission Settings</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('commission-management.settings.update', $setting) }}">
                        @csrf
                        @method('PATCH')
                        <div class="row g-6">
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input type="hidden" name="commission_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" name="commission_enabled" value="1" @checked(old('commission_enabled', $setting->commission_enabled))>
                                    <span class="form-check-label fw-semibold">Commission Enabled</span>
                                </label>
                            </div>
                            <div class="col-md-4"><label class="form-label required">Default Service Type</label><select name="default_service_commission_type" class="form-select form-select-solid">@foreach(\App\Models\CommissionSetting::TYPES as $type)<option value="{{ $type }}" @selected(old('default_service_commission_type', $setting->default_service_commission_type) === $type)>{{ str($type)->headline() }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label required">Default Service Value</label><input type="number" step="0.01" name="default_service_commission_value" value="{{ old('default_service_commission_value', $setting->default_service_commission_value) }}" class="form-control form-control-solid"></div>
                            <div class="col-md-4"><label class="form-label required">Default Product Type</label><select name="default_product_commission_type" class="form-select form-select-solid">@foreach(\App\Models\CommissionSetting::TYPES as $type)<option value="{{ $type }}" @selected(old('default_product_commission_type', $setting->default_product_commission_type) === $type)>{{ str($type)->headline() }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label required">Default Product Value</label><input type="number" step="0.01" name="default_product_commission_value" value="{{ old('default_product_commission_value', $setting->default_product_commission_value) }}" class="form-control form-control-solid"></div>
                            <div class="col-md-4"><label class="form-label required">Calculation Basis</label><select name="calculation_basis" class="form-select form-select-solid">@foreach(\App\Models\CommissionSetting::BASES as $basis)<option value="{{ $basis }}" @selected(old('calculation_basis', $setting->calculation_basis) === $basis)>{{ str($basis)->replace('_', ' ')->headline() }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label required">Earn Trigger</label><select name="earn_trigger" class="form-select form-select-solid">@foreach(\App\Models\CommissionSetting::TRIGGERS as $trigger)<option value="{{ $trigger }}" @selected(old('earn_trigger', $setting->earn_trigger) === $trigger)>{{ str($trigger)->replace('_', ' ')->headline() }}</option>@endforeach</select></div>
                            <div class="col-md-4 d-flex align-items-end"><label class="form-check form-check-custom form-check-solid"><input type="hidden" name="requires_approval" value="0"><input class="form-check-input" type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $setting->requires_approval))><span class="form-check-label">Requires Approval</span></label></div>
                            <div class="col-md-4"><label class="form-label required">Refund Behavior</label><select name="refund_behavior" class="form-select form-select-solid"><option value="reverse" @selected(old('refund_behavior', $setting->refund_behavior) === 'reverse')>Reverse Commission</option></select></div>
                        </div>
                        <input type="hidden" name="allow_manual_adjustment" value="{{ $setting->allow_manual_adjustment ? 1 : 0 }}">
                        <input type="hidden" name="allow_negative_commission" value="{{ $setting->allow_negative_commission ? 1 : 0 }}">
                        <div class="d-flex justify-content-end mt-8"><button class="btn btn-primary">Save Settings</button></div>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-info border-0 shadow-sm">Select a salon to manage commission settings.</div>
        @endif
    </div>
</x-default-layout>
