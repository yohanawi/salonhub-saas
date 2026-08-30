<x-default-layout>
    @section('title') Payslip @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.payslips.show', $item) }} @endsection
    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1">{{ $item->tenant?->name }}</h3>
                    <div class="text-muted">Payslip</div>
                </div>
                <span class="badge badge-light-info">{{ str($item->payment_status)->headline() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-6 mb-8">
                    <div class="col-md-3"><div class="text-muted">Employee</div><div class="fw-bold">{{ $item->staff?->full_name }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Position</div><div class="fw-bold">{{ $item->staff?->job_title ?? '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Branch</div><div class="fw-bold">{{ $item->branch?->name ?? '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted">Period</div><div class="fw-bold">{{ $item->period?->start_date?->format('d M Y') }} - {{ $item->period?->end_date?->format('d M Y') }}</div></div>
                </div>
                <div class="row g-8">
                    <div class="col-md-6">
                        <h4 class="fw-bold mb-4">Earnings</h4>
                        @foreach($earnings as $line)
                            <div class="d-flex justify-content-between border-bottom py-3"><span>{{ str($line->category)->replace('_', ' ')->headline() }}</span><strong>LKR {{ number_format((float) $line->amount, 2) }}</strong></div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-4 fs-5"><span>Gross Earnings</span><strong>LKR {{ number_format((float) $item->gross_pay, 2) }}</strong></div>
                    </div>
                    <div class="col-md-6">
                        <h4 class="fw-bold mb-4">Deductions</h4>
                        @forelse($deductions as $line)
                            <div class="d-flex justify-content-between border-bottom py-3"><span>{{ str($line->category)->replace('_', ' ')->headline() }}</span><strong>LKR {{ number_format((float) $line->amount, 2) }}</strong></div>
                        @empty
                            <div class="text-muted border-bottom py-3">No deductions</div>
                        @endforelse
                        <div class="d-flex justify-content-between pt-4 fs-5"><span>Total Deductions</span><strong>LKR {{ number_format((float) $item->total_deductions, 2) }}</strong></div>
                    </div>
                </div>
                <div class="separator my-8"></div>
                <div class="d-flex justify-content-between align-items-center"><span class="fs-3 fw-bold">Net Salary</span><span class="fs-2 fw-bold text-success">LKR {{ number_format((float) $item->net_pay, 2) }}</span></div>
            </div>
        </div>
    </div>
</x-default-layout>
