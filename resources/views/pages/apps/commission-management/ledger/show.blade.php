<x-default-layout>
    @section('title') Commission Detail @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.ledger.show', $commission) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 py-6 d-flex justify-content-between align-items-center">
                <h3 class="fw-bold mb-0">Commission #{{ $commission->id }}</h3>
                <span class="badge badge-light-primary">{{ $commission->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-6">
                    <div class="col-md-3"><div class="text-muted">Staff</div><div class="fw-bold">{{ $commission->staff?->full_name }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Invoice</div><div class="fw-bold">{{ $commission->invoice?->invoice_number }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Branch</div><div class="fw-bold">{{ $commission->branch?->name }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Earned At</div><div class="fw-bold">{{ $commission->earned_at?->format('d M Y h:i A') }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Gross</div><div class="fw-bold">LKR {{ number_format((float) $commission->gross_amount, 2) }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Discount</div><div class="fw-bold">LKR {{ number_format((float) $commission->discount_amount, 2) }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Base</div><div class="fw-bold">LKR {{ number_format((float) $commission->commission_base, 2) }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Commission</div><div class="fw-bold fs-3">LKR {{ number_format((float) $commission->commission_amount, 2) }}</div></div>
                </div>
            </div>
        </div>
        @if($commission->status === \App\Models\StaffCommission::STATUS_EARNED)
            <div class="d-flex gap-3">
                @can('approve', $commission)
                    <form method="POST" action="{{ route('commission-management.ledger.approve', $commission) }}">@csrf<button class="btn btn-success">Approve</button></form>
                    <form method="POST" action="{{ route('commission-management.ledger.reject', $commission) }}">@csrf<button class="btn btn-light-danger">Reject</button></form>
                @endcan
            </div>
        @endif
    </div>
</x-default-layout>
