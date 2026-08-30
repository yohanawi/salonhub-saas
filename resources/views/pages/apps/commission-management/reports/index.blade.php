<x-default-layout>
    @section('title') Commission Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.reports.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="row g-6 mb-8">
            @foreach([
                ['Total Earned', $totalEarned, 'primary'],
                ['Approved Unpaid', $totalApproved, 'info'],
                ['Paid', $totalPaid, 'success'],
                ['Reversed', $totalReversed, 'danger'],
            ] as [$label, $amount, $color])
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">{{ $label }}</div><div class="fs-2 fw-bold text-{{ $color }}">LKR {{ number_format((float) $amount, 2) }}</div></div></div>
                </div>
            @endforeach
        </div>

        <form method="GET" class="row g-4 mb-8">
            @if($isSuperAdmin)
                <div class="col-md-3"><select name="tenant_id" class="form-select form-select-solid"><option value="">All salons</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>
            @endif
            <div class="col-md-3"><select name="branch_id" class="form-select form-select-solid"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="staff_id" class="form-select form-select-solid"><option value="">All staff</option>@foreach($staffMembers as $member)<option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>{{ $member->full_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
        </form>

        <div class="row g-8">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">By Staff</h3></div>
                    <div class="card-body pt-0">
                        @forelse($byStaff as $row)
                            <div class="d-flex justify-content-between border-bottom py-4"><span>{{ $row->staff?->full_name ?? 'Unassigned' }}</span><strong>LKR {{ number_format((float) $row->total, 2) }}</strong></div>
                        @empty
                            <div class="text-center text-muted py-10">No data.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">By Branch</h3></div>
                    <div class="card-body pt-0">
                        @forelse($byBranch as $row)
                            <div class="d-flex justify-content-between border-bottom py-4"><span>{{ $row->branch?->name ?? 'Unassigned' }}</span><strong>LKR {{ number_format((float) $row->total, 2) }}</strong></div>
                        @empty
                            <div class="text-center text-muted py-10">No data.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
