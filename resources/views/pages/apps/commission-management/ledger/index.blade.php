<x-default-layout>
    @section('title') Commission Ledger @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.ledger.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Commission Ledger</h3></div>
            <div class="card-body">
                <form method="GET" class="row g-4 mb-6">
                    @if($isSuperAdmin)
                        <div class="col-md-3"><select name="tenant_id" class="form-select form-select-solid"><option value="">All salons</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>
                    @endif
                    <div class="col-md-3"><select name="branch_id" class="form-select form-select-solid"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><select name="staff_id" class="form-select form-select-solid"><option value="">All staff</option>@foreach($staffMembers as $member)<option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>{{ $member->full_name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><select name="status" class="form-select form-select-solid"><option value="">All status</option>@foreach(\App\Models\StaffCommission::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Staff</th><th>Invoice</th><th>Item</th><th>Base</th><th>Commission</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($commissions as $commission)
                                <tr>
                                    <td>{{ $commission->staff?->full_name }}</td>
                                    <td>{{ $commission->invoice?->invoice_number }}</td>
                                    <td>{{ $commission->service?->name ?? $commission->product?->name ?? $commission->invoiceItem?->item_name }}</td>
                                    <td>LKR {{ number_format((float) $commission->commission_base, 2) }}</td>
                                    <td class="fw-bold">LKR {{ number_format((float) $commission->commission_amount, 2) }}</td>
                                    <td><span class="badge badge-light-primary">{{ $commission->status_label }}</span></td>
                                    <td class="text-end"><a href="{{ route('commission-management.ledger.show', $commission) }}" class="btn btn-sm btn-light">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-10">No commission records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $commissions->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
