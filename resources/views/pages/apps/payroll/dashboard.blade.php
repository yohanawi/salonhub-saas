<x-default-layout>
    @section('title') Payroll Dashboard @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="row g-6 mb-8">
            @foreach([
                ['Gross Payroll', $grossPayroll, 'primary'],
                ['Net Payroll', $netPayroll, 'success'],
                ['Paid', $paidPayroll, 'info'],
                ['Outstanding', $outstandingPayroll, 'warning'],
            ] as [$label, $amount, $color])
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted fw-semibold">{{ $label }}</div><div class="fs-2 fw-bold text-{{ $color }}">LKR {{ number_format((float) $amount, 2) }}</div></div></div>
                </div>
            @endforeach
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">Recent Payroll Runs</h3>
                <span class="badge badge-light-warning">{{ number_format($pendingApprovals) }} pending review</span>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Run</th><th>Period</th><th>Employees</th><th>Net Pay</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($recentRuns as $run)
                                <tr>
                                    <td class="fw-bold">{{ $run->run_number }}</td>
                                    <td>{{ $run->period?->name }}</td>
                                    <td>{{ number_format($run->employees_count) }}</td>
                                    <td>LKR {{ number_format((float) $run->net_pay, 2) }}</td>
                                    <td><span class="badge badge-light-primary">{{ $run->status_label }}</span></td>
                                    <td class="text-end"><a href="{{ route('payroll.runs.show', $run) }}" class="btn btn-sm btn-light">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">No payroll runs generated yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
