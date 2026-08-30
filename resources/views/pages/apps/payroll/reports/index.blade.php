<x-default-layout>
    @section('title') Payroll Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.reports.index') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="row g-6 mb-8">
            @foreach([
                ['Gross Payroll', $grossPayroll, 'primary'],
                ['Commission', $commissionTotal, 'info'],
                ['Deductions', $deductionsTotal, 'danger'],
                ['Net Payroll', $netPayroll, 'success'],
            ] as [$label, $amount, $color])
                <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">{{ $label }}</div><div class="fs-2 fw-bold text-{{ $color }}">LKR {{ number_format((float) $amount, 2) }}</div></div></div></div>
            @endforeach
        </div>
        <div class="row g-8">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm"><div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Payroll by Branch</h3></div><div class="card-body pt-0">
                    @forelse($byBranch as $row)
                        <div class="d-flex justify-content-between border-bottom py-4"><span>{{ $row->branch?->name ?? 'All branches' }}</span><strong>LKR {{ number_format((float) $row->total, 2) }}</strong></div>
                    @empty
                        <div class="text-center text-muted py-10">No payroll data.</div>
                    @endforelse
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm"><div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Recent Runs</h3></div><div class="card-body pt-0">
                    @forelse($runs as $run)
                        <div class="d-flex justify-content-between border-bottom py-4"><a class="fw-bold" href="{{ route('payroll.runs.show', $run) }}">{{ $run->run_number }}</a><span>LKR {{ number_format((float) $run->net_pay, 2) }}</span></div>
                    @empty
                        <div class="text-center text-muted py-10">No payroll runs.</div>
                    @endforelse
                </div></div>
            </div>
        </div>
    </div>
</x-default-layout>
