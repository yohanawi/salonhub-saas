<x-default-layout>
    @section('title') Staff Commission Overview @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="row g-6 mb-8">
            @foreach ([
                ['Earned', $earnedTotal, 'primary'],
                ['Pending Approval', $pendingApproval, 'warning'],
                ['Approved / Unpaid', $approvedUnpaid, 'info'],
                ['Paid This Month', $paidThisMonth, 'success'],
            ] as [$label, $amount, $color])
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-semibold">{{ $label }}</div>
                            <div class="fs-2 fw-bold text-{{ $color }}">LKR {{ number_format((float) $amount, 2) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Recent Commissions</h3></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Staff</th><th>Invoice</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    @forelse ($recentCommissions as $commission)
                                        <tr>
                                            <td>{{ $commission->staff?->full_name }}</td>
                                            <td>{{ $commission->invoice?->invoice_number }}</td>
                                            <td><span class="badge badge-light-primary">{{ $commission->status_label }}</span></td>
                                            <td class="text-end fw-bold">LKR {{ number_format((float) $commission->commission_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-10">No commissions generated yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Recent Payouts</h3></div>
                    <div class="card-body pt-0">
                        @forelse ($recentPayouts as $payout)
                            <div class="d-flex justify-content-between border-bottom py-4">
                                <div>
                                    <div class="fw-bold">{{ $payout->staff?->full_name }}</div>
                                    <div class="text-muted fs-8">{{ $payout->payout_number }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">LKR {{ number_format((float) $payout->net_payable, 2) }}</div>
                                    <span class="badge badge-light-info">{{ str($payout->status)->headline() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-10">No payouts yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
