<x-default-layout>
    @section('title') Salary Advances @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.advances.index') }} @endsection

    @php $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2); @endphp

    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')

        <div class="row g-5 mb-5">
            @foreach ([
                ['label' => 'Awaiting approval', 'value' => $summary['requested'], 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
                ['label' => 'Approved, not yet paid out', 'value' => $summary['approved'], 'color' => 'primary', 'icon' => 'bi-check2-circle'],
                ['label' => 'Outstanding to recover', 'value' => $summary['outstanding'], 'color' => 'danger', 'icon' => 'bi-arrow-repeat'],
            ] as $tile)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center p-5">
                            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-{{ $tile['color'] }}"><i class="bi {{ $tile['icon'] }} text-{{ $tile['color'] }} fs-2"></i></div></div>
                            <div>
                                <div class="fs-3 fw-bolder text-gray-900">{{ $money($tile['value']) }}</div>
                                <div class="text-muted fs-8">{{ $tile['label'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-5">
            @can('create', \App\Models\SalaryAdvance::class)
                <div class="col-xl-4">
                    <form method="POST" action="{{ route('payroll.advances.store') }}" class="card border-0 shadow-sm">
                        @csrf
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold text-gray-900 mb-1"><i class="bi bi-cash-stack text-primary me-2"></i>New Salary Advance</h3>
                                <div class="text-muted fs-8">Recovered automatically from the staff member's next payroll(s).</div>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="mb-5">
                                <label class="form-label required">Staff Member</label>
                                <select name="staff_id" class="form-select form-select-solid" required>
                                    <option value="">Select staff</option>
                                    @foreach ($staffMembers as $member)
                                        <option value="{{ $member->id }}" @selected((string) old('staff_id') === (string) $member->id)>{{ $member->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-4 mb-5">
                                <div class="col-6">
                                    <label class="form-label required">Amount</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control form-control-solid" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="request_date" value="{{ old('request_date', today()->toDateString()) }}" class="form-control form-control-solid">
                                </div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label required">Recovery</label>
                                <select name="repayment_type" id="advance_repayment_type" class="form-select form-select-solid">
                                    <option value="one_time" @selected(old('repayment_type') !== 'installments')>In full from next payroll</option>
                                    <option value="installments" @selected(old('repayment_type') === 'installments')>In monthly installments</option>
                                </select>
                            </div>
                            <div class="mb-5 {{ old('repayment_type') === 'installments' ? '' : 'd-none' }}" id="advance_installment_group">
                                <label class="form-label required">Installment per Payroll</label>
                                <input type="number" step="0.01" min="0.01" name="installment_amount" value="{{ old('installment_amount') }}" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" value="{{ old('reason') }}" class="form-control form-control-solid" placeholder="e.g. Medical expense">
                            </div>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="approve_now" value="1" @checked(old('approve_now'))>
                                <span class="form-check-label">Approve immediately</span>
                            </label>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Record Advance</button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Advances</h3></div>
                        <div class="card-toolbar">
                            <form method="GET" class="d-flex gap-2">
                                <select name="status" class="form-select form-select-solid form-select-sm w-150px" onchange="this.form.submit()">
                                    <option value="">All statuses</option>
                                    @foreach (\App\Models\SalaryAdvance::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @if ($advances->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-4">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th>Advance</th>
                                            <th>Staff</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Outstanding</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end min-w-200px"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-7">
                                        @foreach ($advances as $advance)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-gray-900">{{ $advance->advance_number }}</div>
                                                    <div class="text-muted fs-8">{{ $advance->request_date?->format('M d, Y') }}{{ $advance->reason ? ' · ' . $advance->reason : '' }}</div>
                                                    @if ($advance->repayment_type === 'installments')
                                                        <div class="text-muted fs-8">{{ $money($advance->installment_amount) }} per payroll</div>
                                                    @endif
                                                </td>
                                                <td>{{ $advance->staff?->full_name }}</td>
                                                <td class="text-end">
                                                    {{ $money($advance->approved_amount > 0 ? $advance->approved_amount : $advance->requested_amount) }}
                                                    @if ($advance->approved_amount > 0 && (float) $advance->approved_amount !== (float) $advance->requested_amount)
                                                        <div class="text-muted fs-8">requested {{ number_format((float) $advance->requested_amount, 2) }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-semibold">{{ in_array($advance->status, ['paid', 'recovered']) ? $money($advance->outstanding_amount) : '—' }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $advance->status_color }}">{{ $advance->status_label }}</span>
                                                    @if ($advance->rejection_reason)
                                                        <div class="text-muted fs-9">{{ $advance->rejection_reason }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @can('manage', $advance)
                                                        @if ($advance->status === 'requested')
                                                            <form method="POST" action="{{ route('payroll.advances.approve', $advance) }}" class="d-inline">@csrf<button class="btn btn-sm btn-light-success">Approve</button></form>
                                                        @endif
                                                        @if ($advance->status === 'approved')
                                                            <form method="POST" action="{{ route('payroll.advances.pay', $advance) }}" class="d-inline" onsubmit="return confirm('Mark this advance as paid out to the staff member?');">
                                                                @csrf
                                                                <input type="hidden" name="payment_method" value="cash">
                                                                <button class="btn btn-sm btn-primary">Mark Paid</button>
                                                            </form>
                                                        @endif
                                                        @if (in_array($advance->status, ['requested', 'approved']))
                                                            <form method="POST" action="{{ route('payroll.advances.reject', $advance) }}" class="d-inline" onsubmit="return confirm('Reject this advance?');">@csrf<button class="btn btn-sm btn-light-danger">Reject</button></form>
                                                        @endif
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-5">{{ $advances->links() }}</div>
                        @else
                            <div class="text-center text-muted py-15">No salary advances yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('advance_repayment_type')?.addEventListener('change', function() {
                document.getElementById('advance_installment_group').classList.toggle('d-none', this.value !== 'installments');
            });
        </script>
    @endpush
</x-default-layout>
