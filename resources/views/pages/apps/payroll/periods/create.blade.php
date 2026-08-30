<x-default-layout>
    @section('title') Create Payroll Period @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.periods.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Create Payroll Period</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('payroll.periods.store') }}">
                    @csrf
                    @if($isSuperAdmin)
                        <div class="mb-6"><label class="form-label required">Salon</label><select name="tenant_id" class="form-select form-select-solid" onchange="window.location='{{ route('payroll.periods.create') }}?tenant_id='+this.value"><option value="">Select salon</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>
                    @endif
                    <div class="row g-6">
                        <div class="col-md-4"><label class="form-label required">Name</label><input name="name" value="{{ old('name', $period->name) }}" class="form-control form-control-solid"></div>
                        <div class="col-md-4"><label class="form-label">Branch</label><select name="branch_id" class="form-select form-select-solid"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $period->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select form-select-solid"><option value="open">Open</option><option value="draft">Draft</option></select></div>
                        <div class="col-md-4"><label class="form-label required">Start Date</label><input type="date" name="start_date" value="{{ old('start_date', $period->start_date?->toDateString()) }}" class="form-control form-control-solid"></div>
                        <div class="col-md-4"><label class="form-label required">End Date</label><input type="date" name="end_date" value="{{ old('end_date', $period->end_date?->toDateString()) }}" class="form-control form-control-solid"></div>
                        <div class="col-md-4"><label class="form-label">Pay Date</label><input type="date" name="pay_date" value="{{ old('pay_date', $period->pay_date?->toDateString()) }}" class="form-control form-control-solid"></div>
                    </div>
                    <div class="d-flex justify-content-end gap-3 mt-8"><a href="{{ route('payroll.periods.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary" @disabled($isSuperAdmin && ! $selectedTenant)>Save Period</button></div>
                </form>
            </div>
        </div>
    </div>
</x-default-layout>
