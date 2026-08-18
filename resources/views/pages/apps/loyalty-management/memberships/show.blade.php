<x-default-layout>
    @section('title') Membership {{ $membership->membership_number }} @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.memberships.show', $membership) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex justify-content-between flex-wrap gap-5">
                    <div><h1 class="fw-bolder mb-2">{{ $membership->membership_number }}</h1><div class="text-muted">{{ $membership->customer?->full_name }} - {{ $membership->plan?->name }}</div></div>
                    <span class="badge badge-light-primary align-self-start">{{ $membership->status_label }}</span>
                </div>
                <div class="row g-6 mt-4">
                    <div class="col-md-3"><div class="text-muted">Start</div><div class="fw-bold">{{ $membership->start_date?->format('M d, Y') }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Expiry</div><div class="fw-bold">{{ $membership->end_date?->format('M d, Y') }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Paid</div><div class="fw-bold">LKR {{ number_format((float) $membership->price_paid + (float) $membership->joining_fee_paid, 2) }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Invoice</div><div class="fw-bold">{{ $membership->invoice?->invoice_number ?? '-' }}</div></div>
                </div>
            </div>
        </div>
        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100"><div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Benefit Usage</h3></div><div class="card-body pt-3">@forelse($membership->usages as $usage)<div class="d-flex justify-content-between border-bottom py-3"><span>{{ str($usage->benefit?->benefit_type)->replace('_', ' ')->headline() }}</span><span class="fw-bold">LKR {{ number_format((float) $usage->discount_amount, 2) }}</span></div>@empty<div class="text-muted py-6">No benefit usage recorded yet.</div>@endforelse</div></div>
            </div>
            <div class="col-xl-5">
                @can('cancel', $membership)
                    @if($membership->status === 'active')
                        <form method="POST" action="{{ route('loyalty-management.memberships.cancel', $membership) }}" class="card border-0 shadow-sm">
                            @csrf
                            <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Cancel Membership</h3></div>
                            <div class="card-body pt-3"><textarea name="cancellation_reason" rows="4" class="form-control form-control-solid mb-5" placeholder="Reason" required></textarea><button class="btn btn-light-danger">Cancel Membership</button></div>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</x-default-layout>
