<x-default-layout>
    @section('title') Commission Payout @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.payouts.show', $payout) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">{{ $payout->payout_number }}</h3>
                <span class="badge badge-light-info">{{ str($payout->status)->headline() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-6">
                    <div class="col-md-3"><div class="text-muted">Staff</div><div class="fw-bold">{{ $payout->staff?->full_name }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Period</div><div class="fw-bold">{{ $payout->period_start?->format('d M Y') }} - {{ $payout->period_end?->format('d M Y') }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Gross</div><div class="fw-bold">LKR {{ number_format((float) $payout->gross_commission, 2) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Adjustment</div><div class="fw-bold">LKR {{ number_format((float) $payout->adjustment_amount, 2) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Net Payable</div><div class="fw-bold fs-3">LKR {{ number_format((float) $payout->net_payable, 2) }}</div></div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-3 mb-8">
            @if($payout->status === \App\Models\CommissionPayout::STATUS_PENDING)
                @can('approve', $payout)
                    <form method="POST" action="{{ route('commission-management.payouts.approve', $payout) }}">@csrf<button class="btn btn-success">Approve Payout</button></form>
                @endcan
            @endif
            @if($payout->status === \App\Models\CommissionPayout::STATUS_APPROVED)
                @can('pay', $payout)
                    <form method="POST" action="{{ route('commission-management.payouts.pay', $payout) }}" class="d-flex gap-3">
                        @csrf
                        <input name="payment_method" class="form-control form-control-solid w-200px" placeholder="Payment method" required>
                        <input name="payment_reference" class="form-control form-control-solid w-250px" placeholder="Reference">
                        <button class="btn btn-primary">Mark Paid</button>
                    </form>
                @endcan
            @endif
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Included Commissions</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Commission</th><th>Invoice</th><th>Earned</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @foreach($payout->items as $item)
                                <tr>
                                    <td>#{{ $item->commission?->id }}</td>
                                    <td>{{ $item->commission?->invoice?->invoice_number }}</td>
                                    <td>{{ $item->commission?->earned_at?->format('d M Y') }}</td>
                                    <td class="text-end fw-bold">LKR {{ number_format((float) $item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
