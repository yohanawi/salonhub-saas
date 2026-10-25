<x-default-layout>
    @section('title') Payroll Run @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.runs.show', $run) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">{{ $run->run_number }}</h3>
                <span class="badge badge-light-primary">{{ $run->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-6">
                    <div class="col-md-2"><div class="text-muted">Employees</div><div class="fw-bold">{{ number_format($run->employees_count) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Basic</div><div class="fw-bold">LKR {{ number_format((float) $run->basic_salary_total, 2) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Commission</div><div class="fw-bold">LKR {{ number_format((float) $run->commission_total, 2) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Gross</div><div class="fw-bold">LKR {{ number_format((float) $run->gross_pay, 2) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Deductions</div><div class="fw-bold">LKR {{ number_format((float) $run->total_deductions, 2) }}</div></div>
                    <div class="col-md-2"><div class="text-muted">Net Pay</div><div class="fw-bold fs-4">LKR {{ number_format((float) $run->net_pay, 2) }}</div></div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3 mb-8">
            @if($run->status === \App\Models\PayrollRun::STATUS_CALCULATED)
                @can('approve', $run)
                    <form method="POST" action="{{ route('payroll.runs.submit', $run) }}">@csrf<button class="btn btn-info">Submit Review</button></form>
                    <form method="POST" action="{{ route('payroll.runs.approve', $run) }}">@csrf<button class="btn btn-success">Approve Payroll</button></form>
                @endcan
            @endif
            @if($run->status === \App\Models\PayrollRun::STATUS_UNDER_REVIEW)
                @can('approve', $run)
                    <form method="POST" action="{{ route('payroll.runs.approve', $run) }}">@csrf<button class="btn btn-success">Approve Payroll</button></form>
                    <form method="POST" action="{{ route('payroll.runs.reject', $run) }}">@csrf<button class="btn btn-light-danger">Reject</button></form>
                @endcan
            @endif
            @if($run->status === \App\Models\PayrollRun::STATUS_APPROVED)
                @can('pay', $run)
                    <form method="POST" action="{{ route('payroll.runs.payments.store', $run) }}" class="d-flex gap-3">
                        @csrf
                        <input name="payment_method" class="form-control form-control-solid w-200px" placeholder="Payment method" required>
                        <input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="form-control form-control-solid w-175px" required>
                        <input name="payment_reference" class="form-control form-control-solid w-250px" placeholder="Reference">
                        <button class="btn btn-primary">Pay Payroll</button>
                    </form>
                @endcan
            @endif
            @if(in_array($run->status, [\App\Models\PayrollRun::STATUS_APPROVED, \App\Models\PayrollRun::STATUS_PAID, \App\Models\PayrollRun::STATUS_CLOSED], true))
                @can('pay', $run)
                    <a href="{{ route('payroll.runs.bank-export', $run) }}" class="btn btn-light-primary"><i class="bi bi-bank me-1"></i>Bank Transfer File</a>
                @endcan
                @can('approve', $run)
                    <form method="POST" action="{{ route('payroll.runs.send-payslips', $run) }}" class="d-flex gap-2 align-items-center" onsubmit="return confirm('Send payslips to all staff in this run?');">
                        @csrf
                        <label class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="channels[]" value="email" checked><span class="form-check-label">Email (PDF)</span></label>
                        <label class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="channels[]" value="message"><span class="form-check-label">SMS / WhatsApp</span></label>
                        <button class="btn btn-light-success"><i class="bi bi-send me-1"></i>Send All Payslips</button>
                    </form>
                @endcan
            @endif
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Staff Payroll</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Staff</th><th>Basic</th><th>Commission</th><th>Tips</th><th>Gross</th><th>EPF (8%)</th><th>Deductions</th><th>Net</th><th>Payment</th><th class="text-end">Payslip</th></tr></thead>
                        <tbody>
                            @foreach($run->items as $item)
                                <tr>
                                    <td class="fw-bold">{{ $item->staff?->full_name }}</td>
                                    <td>LKR {{ number_format((float) $item->basic_pay, 2) }}</td>
                                    <td>LKR {{ number_format((float) $item->commission_amount, 2) }}</td>
                                    <td>LKR {{ number_format((float) $item->tip_amount, 2) }}</td>
                                    <td>LKR {{ number_format((float) $item->gross_pay, 2) }}</td>
                                    <td>LKR {{ number_format((float) $item->epf_employee_amount, 2) }}</td>
                                    <td>LKR {{ number_format((float) $item->total_deductions, 2) }}</td>
                                    <td class="fw-bold">LKR {{ number_format((float) $item->net_pay, 2) }}</td>
                                    <td><span class="badge badge-light-info">{{ str($item->payment_status)->headline() }}</span></td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('payroll.payslips.show', $item) }}" class="btn btn-sm btn-light">View</a>
                                        <a href="{{ route('payroll.payslips.pdf', $item) }}" class="btn btn-sm btn-light-primary" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                        @if ($item->payslip_sent_at)
                                            <i class="bi bi-check2-all text-success ms-1" title="Sent {{ $item->payslip_sent_at->format('M d, H:i') }}"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
