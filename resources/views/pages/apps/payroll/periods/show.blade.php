<x-default-layout>
    @section('title') Payroll Period @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.periods.show', $period) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 py-6 d-flex justify-content-between align-items-center">
                <h3 class="fw-bold mb-0">{{ $period->name }}</h3>
                <span class="badge badge-light-primary">{{ $period->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-6">
                    <div class="col-md-3"><div class="text-muted">Branch</div><div class="fw-bold">{{ $period->branch?->name ?? 'All branches' }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Start</div><div class="fw-bold">{{ $period->start_date?->format('d M Y') }}</div></div>
                    <div class="col-md-3"><div class="text-muted">End</div><div class="fw-bold">{{ $period->end_date?->format('d M Y') }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Pay Date</div><div class="fw-bold">{{ $period->pay_date?->format('d M Y') ?? '-' }}</div></div>
                </div>
            </div>
        </div>
        @can('calculate', \App\Models\PayrollRun::class)
            <form method="POST" action="{{ route('payroll.periods.generate', $period) }}" class="mb-8">@csrf<button class="btn btn-primary">Generate Payroll</button></form>
        @endcan
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Runs</h3></div>
            <div class="card-body pt-0">
                @forelse($period->runs as $run)
                    <div class="d-flex justify-content-between border-bottom py-4"><a class="fw-bold" href="{{ route('payroll.runs.show', $run) }}">{{ $run->run_number }}</a><span>LKR {{ number_format((float) $run->net_pay, 2) }}</span></div>
                @empty
                    <div class="text-center text-muted py-10">No payroll run generated.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-default-layout>
