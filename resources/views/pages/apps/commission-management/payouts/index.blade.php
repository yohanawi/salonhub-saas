<x-default-layout>
    @section('title') Commission Payouts @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.payouts.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\CommissionPayout::class)
                <a href="{{ route('commission-management.payouts.create', request()->only('tenant_id')) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Create Payout</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Payout</th><th>Staff</th><th>Period</th><th>Gross</th><th>Net Payable</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($payouts as $payout)
                                <tr>
                                    <td class="fw-bold">{{ $payout->payout_number }}</td>
                                    <td>{{ $payout->staff?->full_name }}</td>
                                    <td>{{ $payout->period_start?->format('d M Y') }} - {{ $payout->period_end?->format('d M Y') }}</td>
                                    <td>LKR {{ number_format((float) $payout->gross_commission, 2) }}</td>
                                    <td class="fw-bold">LKR {{ number_format((float) $payout->net_payable, 2) }}</td>
                                    <td><span class="badge badge-light-info">{{ str($payout->status)->headline() }}</span></td>
                                    <td class="text-end"><a href="{{ route('commission-management.payouts.show', $payout) }}" class="btn btn-sm btn-light">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-10">No payouts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $payouts->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
