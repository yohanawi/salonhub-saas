<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    @php
        $hasStaffStatements = $payslips->isNotEmpty() || $commissionPayouts->isNotEmpty();
    @endphp

    @if ($subscriptions->isNotEmpty() || ! $hasStaffStatements)
    <!--begin::Statements-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Header-->
        <div class="card-header card-header-stretch">
            <!--begin::Title-->
            <div class="card-title">
                <h3 class="m-0 text-gray-800">Statement</h3>
            </div>
            <!--end::Title-->
            @if ($subscriptions->isNotEmpty())
                <!--begin::Toolbar-->
                <div class="card-toolbar m-0">
                    <!--begin::Tab nav-->
                    <ul class="nav nav-stretch fs-5 fw-semibold nav-line-tabs border-transparent" role="tablist">
                        @foreach ($subscriptions as $year => $yearSubscriptions)
                            <li class="nav-item" role="presentation">
                                <a id="kt_statements_{{ $loop->index }}_tab"
                                    class="nav-link text-active-gray-800 me-4 {{ $loop->first ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" href="#kt_statements_{{ $loop->index }}">
                                    {{ $year }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <!--end::Tab nav-->
                </div>
                <!--end::Toolbar-->
            @endif
        </div>
        <!--end::Header-->
        <!--begin::Tab Content-->
        <div id="kt_statements_tab_content" class="tab-content">
            @forelse ($subscriptions as $year => $yearSubscriptions)
                <!--begin::Tab panel-->
                <div id="kt_statements_{{ $loop->index }}"
                    class="card-body p-0 tab-pane fade show {{ $loop->first ? 'active' : '' }}" role="tabpanel">
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9">
                            <!--begin::Thead-->
                            <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                                <tr>
                                    <th class="min-w-175px ps-9">Date</th>
                                    <th class="min-w-350px">Plan</th>
                                    <th class="min-w-125px">Amount</th>
                                    <th class="min-w-125px text-center">Status</th>
                                </tr>
                            </thead>
                            <!--end::Thead-->
                            <!--begin::Tbody-->
                            <tbody class="fs-6 fw-semibold text-gray-600">
                                @foreach ($yearSubscriptions as $yearSubscription)
                                    <tr>
                                        <td class="ps-9">{{ $yearSubscription->starts_at?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>{{ $yearSubscription->plan?->name ?? 'Plan' }}
                                            ({{ ucfirst($yearSubscription->plan?->billing_period ?? 'monthly') }})</td>
                                        <td class="text-success">
                                            ${{ number_format((float) ($yearSubscription->price ?? $yearSubscription->plan?->price ?? 0), 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $yearSubscription->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($yearSubscription->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <!--end::Tbody-->
                        </table>
                        <!--end::Table-->
                    </div>
                </div>
                <!--end::Tab panel-->
            @empty
                <div class="card-body p-0">
                    <div class="text-muted fs-6 py-10 text-center">No statements available yet.</div>
                </div>
            @endforelse
        </div>
        <!--end::Tab Content-->
    </div>
    <!--end::Statements-->
    @endif

    @if ($payslips->isNotEmpty())
        <!--begin::Payslips-->
        <div class="card mb-5 mb-xl-10">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="m-0 text-gray-800">My Payslips</h3>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9">
                        <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                            <tr>
                                <th class="min-w-175px ps-9">Period</th>
                                <th class="min-w-125px text-end">Gross Pay</th>
                                <th class="min-w-125px text-end">Deductions</th>
                                <th class="min-w-125px text-end">Net Pay</th>
                                <th class="min-w-100px text-center">Status</th>
                                <th class="min-w-100px text-end pe-9"></th>
                            </tr>
                        </thead>
                        <tbody class="fs-6 fw-semibold text-gray-600">
                            @foreach ($payslips as $payslip)
                                <tr>
                                    <td class="ps-9">
                                        <div class="text-gray-800">{{ $payslip->period?->name ?? 'Payroll period' }}</div>
                                        @if ($payslip->period)
                                            <div class="text-muted fs-7">
                                                {{ $payslip->period->start_date?->format('M d, Y') }} – {{ $payslip->period->end_date?->format('M d, Y') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $currency }} {{ number_format((float) $payslip->gross_pay, 2) }}</td>
                                    <td class="text-end text-danger">{{ $currency }} {{ number_format((float) $payslip->total_deductions, 2) }}</td>
                                    <td class="text-end text-success fw-bold">{{ $currency }} {{ number_format((float) $payslip->net_pay, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-{{ $payslip->payment_status === 'paid' ? 'success' : 'warning' }}">
                                            {{ str($payslip->payment_status)->replace('_', ' ')->headline() }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-9">
                                        @can('view', $payslip)
                                            <a href="{{ route('payroll.payslips.show', $payslip) }}" class="btn btn-sm btn-light-primary">View</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Payslips-->
    @endif

    @if ($commissionPayouts->isNotEmpty())
        <!--begin::Commission payouts-->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="m-0 text-gray-800">My Commission Payouts</h3>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9">
                        <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                            <tr>
                                <th class="min-w-125px ps-9">Payout</th>
                                <th class="min-w-175px">Period</th>
                                <th class="min-w-125px text-end">Gross</th>
                                <th class="min-w-125px text-end">Adjustments</th>
                                <th class="min-w-125px text-end">Net Payable</th>
                                <th class="min-w-100px text-center pe-9">Status</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6 fw-semibold text-gray-600">
                            @foreach ($commissionPayouts as $payout)
                                <tr>
                                    <td class="ps-9 text-gray-800">{{ $payout->payout_number ?? '#' . $payout->id }}</td>
                                    <td>{{ $payout->period_start?->format('M d, Y') }} – {{ $payout->period_end?->format('M d, Y') }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format((float) $payout->gross_commission, 2) }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format((float) $payout->adjustment_amount, 2) }}</td>
                                    <td class="text-end text-success fw-bold">{{ $currency }} {{ number_format((float) $payout->net_payable, 2) }}</td>
                                    <td class="text-center pe-9">
                                        <span class="badge badge-light-{{ match ($payout->status) { 'paid' => 'success', 'approved' => 'primary', default => 'warning' } }}">
                                            {{ ucfirst($payout->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Commission payouts-->
    @endif
</x-default-layout>
