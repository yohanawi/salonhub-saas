<x-default-layout>
    @section('title') Payroll Dashboard @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-60px me-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-wallet2 text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-primary mb-3">Payroll Command</div>
                            <h1 class="fw-bolder text-gray-900 mb-1">Payroll Dashboard</h1>
                            <div class="text-muted">Monitor salary totals, approvals, and recent payroll runs.</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('payroll.periods.index') }}" class="btn btn-light-primary">
                            <i class="bi bi-calendar2-week me-2"></i>Payroll Periods
                        </a>
                        <a href="{{ route('payroll.runs.index') }}" class="btn btn-primary">
                            <i class="bi bi-play-circle me-2"></i>Payroll Runs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            @foreach([
                ['Gross Payroll', $grossPayroll, 'primary', 'bi-cash-stack'],
                ['Net Payroll', $netPayroll, 'success', 'bi-bank'],
                ['Paid', $paidPayroll, 'info', 'bi-check2-circle'],
                ['Outstanding', $outstandingPayroll, 'warning', 'bi-hourglass-split'],
            ] as [$label, $amount, $color, $icon])
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center p-6">
                            <div class="symbol symbol-50px me-5">
                                <div class="symbol-label bg-light-{{ $color }}">
                                    <i class="bi {{ $icon }} text-{{ $color }} fs-2"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-500 fw-semibold fs-7 text-uppercase">{{ $label }}</div>
                                <div class="fs-3 fw-bolder text-gray-900">LKR {{ number_format((float) $amount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-list-check text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1">Recent Payroll Runs</h3>
                            <div class="text-muted fs-7">Latest generated payroll batches</div>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-warning">{{ number_format($pendingApprovals) }} pending review</span>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Run</th>
                                <th>Period</th>
                                <th>Employees</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRuns as $run)
                                <tr>
                                    <td>
                                        <a href="{{ route('payroll.runs.show', $run) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $run->run_number }}</a>
                                        <div class="text-muted fs-8">{{ $run->branch?->name ?? 'All branches' }}</div>
                                    </td>
                                    <td class="text-gray-700">{{ $run->period?->name }}</td>
                                    <td><span class="badge badge-light">{{ number_format($run->employees_count) }}</span></td>
                                    <td class="fw-bold text-gray-900">LKR {{ number_format((float) $run->net_pay, 2) }}</td>
                                    <td><span class="badge badge-light-primary">{{ $run->status_label }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('payroll.runs.show', $run) }}" class="btn btn-icon btn-sm btn-light-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-12">
                                        <div class="symbol symbol-60px mx-auto mb-4">
                                            <div class="symbol-label bg-light">
                                                <i class="bi bi-wallet2 text-muted fs-1"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-gray-800">No payroll runs generated yet.</div>
                                        <div class="text-muted fs-7">Create a payroll period and generate payroll to begin.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
