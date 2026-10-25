<x-default-layout>
    @section('title') Payslip @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.payslips.show', $item) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="d-flex flex-wrap gap-3 mb-5 justify-content-end">
            <a href="{{ route('payroll.payslips.pdf', $item) }}" class="btn btn-sm btn-light-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
            @if (in_array($item->status, ['approved', 'paid'], true) && $item->run && auth()->user()->can('approve', $item->run))
                <form method="POST" action="{{ route('payroll.payslips.send', $item) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="channels[]" value="email">
                    <button class="btn btn-sm btn-light-success" @disabled(! ($item->staff?->email || $item->staff?->user?->email))><i class="bi bi-envelope me-1"></i>Email Payslip</button>
                </form>
                <form method="POST" action="{{ route('payroll.payslips.send', $item) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="channels[]" value="message">
                    <button class="btn btn-sm btn-light-success" @disabled(! $item->staff?->phone)><i class="bi bi-whatsapp me-1"></i>Send by SMS / WhatsApp</button>
                </form>
            @endif
            @if ($item->payslip_sent_at)
                <span class="badge badge-light-success align-self-center">Sent {{ $item->payslip_sent_at->format('M d, H:i') }}</span>
            @endif
        </div>
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
                @if ((float) $item->epf_employer_amount > 0 || (float) $item->etf_employer_amount > 0)
                    <div class="rounded-3 bg-light p-4 mb-6 fs-7">
                        <strong>Employer contributions</strong> (not deducted):
                        EPF 12% LKR {{ number_format((float) $item->epf_employer_amount, 2) }} ·
                        ETF 3% LKR {{ number_format((float) $item->etf_employer_amount, 2) }} ·
                        on EPF earnings of LKR {{ number_format((float) $item->statutory_earnings, 2) }}
                    </div>
                @endif
                <div class="d-flex justify-content-between align-items-center"><span class="fs-3 fw-bold">Net Salary</span><span class="fs-2 fw-bold text-success">LKR {{ number_format((float) $item->net_pay, 2) }}</span></div>
            </div>
        </div>
    </div>
</x-default-layout>
