<x-default-layout>
    @section('title') EPF / ETF @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.statutory.index') }} @endsection

    @php $money = fn ($value) => number_format((float) $value, 2); @endphp

    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')

        @unless ($epfEnabled)
            <div class="alert alert-warning border-0 shadow-sm mb-5">
                EPF / ETF is switched off. Turn on <strong>Apply EPF / ETF</strong> under Settings → Team → Payroll, then regenerate payroll.
            </div>
        @endunless

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">EPF / ETF Contributions</h3>
                    <div class="text-muted fs-7">
                        Approved and paid payroll only.
                        @if ($employerEpfNumber)
                            Employer EPF No: <strong>{{ $employerEpfNumber }}</strong>
                        @endif
                    </div>
                </div>
                <form method="GET" class="d-flex gap-2">
                    <select name="period_id" class="form-select form-select-solid form-select-sm w-250px" onchange="this.form.submit()">
                        @forelse ($periods as $option)
                            <option value="{{ $option->id }}" @selected($period?->id === $option->id)>{{ $option->name }}</option>
                        @empty
                            <option value="">No payroll periods</option>
                        @endforelse
                    </select>
                    @if ($period && $rows->isNotEmpty())
                        <a href="{{ route('payroll.statutory.export', ['period_id' => $period->id]) }}" class="btn btn-sm btn-light-primary"><i class="bi bi-download me-1"></i>CSV</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="row g-5 mb-5">
            @foreach ([
                ['label' => 'EPF Employee', 'value' => $totals['epf_employee']],
                ['label' => 'EPF Employer', 'value' => $totals['epf_employer']],
                ['label' => 'EPF Total to Remit', 'value' => $totals['epf_total']],
                ['label' => 'ETF to Remit', 'value' => $totals['etf']],
            ] as $tile)
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100"><div class="card-body p-5">
                        <div class="fs-3 fw-bolder text-gray-900">{{ $currency }} {{ $money($tile['value']) }}</div>
                        <div class="text-muted fs-8">{{ $tile['label'] }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if ($rows->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th>Employee</th>
                                    <th>EPF No.</th>
                                    <th class="text-end">Earnings</th>
                                    <th class="text-end">EPF Employee</th>
                                    <th class="text-end">EPF Employer</th>
                                    <th class="text-end">EPF Total</th>
                                    <th class="text-end">ETF</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @foreach ($rows as $row)
                                    <tr>
                                        <td><div class="fw-semibold">{{ $row['employee_name'] }}</div><div class="text-muted fs-8">{{ $row['employee_code'] }}</div></td>
                                        <td>{!! $row['epf_number'] ? e($row['epf_number']) : '<span class="badge badge-light-danger">Missing</span>' !!}</td>
                                        <td class="text-end">{{ $money($row['earnings']) }}</td>
                                        <td class="text-end">{{ $money($row['epf_employee']) }}</td>
                                        <td class="text-end">{{ $money($row['epf_employer']) }}</td>
                                        <td class="text-end fw-bold">{{ $money($row['epf_total']) }}</td>
                                        <td class="text-end fw-bold">{{ $money($row['etf']) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bolder border-top">
                                    <td colspan="2">Total</td>
                                    <td class="text-end">{{ $money($totals['earnings']) }}</td>
                                    <td class="text-end">{{ $money($totals['epf_employee']) }}</td>
                                    <td class="text-end">{{ $money($totals['epf_employer']) }}</td>
                                    <td class="text-end">{{ $money($totals['epf_total']) }}</td>
                                    <td class="text-end">{{ $money($totals['etf']) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-15">No EPF / ETF contributions for this period yet.</div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
