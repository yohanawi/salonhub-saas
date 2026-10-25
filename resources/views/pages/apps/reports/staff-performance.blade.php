<x-default-layout>
    @section('title') Staff Performance @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('reports.staff-performance') }} @endsection

    @php
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 0);
        $from = $report['from'];
        $to = $report['to'];
        $totals = $report['totals'];
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Staff Performance</h3>
                    <div class="text-muted fs-7">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }} · {{ $selectedBranch?->name ?? 'All branches' }}</div>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-end">
                    @include('pages/apps.reports._filters')
                    @include('partials.reports._export-buttons', ['route' => 'reports.staff-performance.export'])
                </div>
            </div>
        </div>

        <div class="row g-5 mb-5">
            @foreach ([
                ['label' => 'Revenue by staff', 'value' => $money($totals['revenue'])],
                ['label' => 'Services performed', 'value' => number_format($totals['services'])],
                ['label' => 'Rebooking rate (' . \App\Services\Reports\StaffPerformanceService::REBOOK_WINDOW_DAYS . ' days)', 'value' => $totals['rebooking_rate'] === null ? '—' : $totals['rebooking_rate'] . '%'],
                ['label' => 'Average rating', 'value' => $totals['rating'] === null ? '—' : $totals['rating'] . ' ★'],
            ] as $tile)
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100"><div class="card-body p-5">
                        <div class="fs-2 fw-bolder text-gray-900">{{ $tile['value'] }}</div>
                        <div class="text-muted fs-8">{{ $tile['label'] }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if ($report['rows']->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-4 fs-7">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-175px">Staff</th>
                                    <th class="text-end">Services</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Avg Bill</th>
                                    <th class="text-end">Clients</th>
                                    <th class="text-end">Rebooking</th>
                                    <th class="text-end">Rating</th>
                                    <th class="text-end">No-shows</th>
                                    <th class="text-end">Commission</th>
                                    <th class="text-end">Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['rows'] as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $row['staff']->full_name }}</div>
                                            <div class="text-muted fs-8">{{ $row['staff']->job_title }}</div>
                                        </td>
                                        <td class="text-end">{{ number_format($row['services']) }}</td>
                                        <td class="text-end">
                                            <div class="fw-semibold">{{ $money($row['total_revenue']) }}</div>
                                            @if ($row['product_revenue'] > 0)
                                                <div class="text-muted fs-9">incl. {{ $money($row['product_revenue']) }} retail</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $money($row['average_bill']) }}</td>
                                        <td class="text-end">{{ number_format($row['clients']) }}</td>
                                        <td class="text-end">
                                            @if ($row['rebooking_rate'] === null)
                                                —
                                            @else
                                                <span class="badge badge-light-{{ $row['rebooking_rate'] >= 50 ? 'success' : ($row['rebooking_rate'] >= 25 ? 'warning' : 'danger') }}">{{ $row['rebooking_rate'] }}%</span>
                                                <div class="text-muted fs-9">{{ $row['rebooked'] }}/{{ $row['completed'] }} visits</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $row['rating'] ? $row['rating'] . ' ★' : '—' }}@if ($row['rating_count'])<div class="text-muted fs-9">{{ $row['rating_count'] }} reviews</div>@endif</td>
                                        <td class="text-end">{{ $row['no_shows'] }}</td>
                                        <td class="text-end">{{ $money($row['commission']) }}</td>
                                        <td class="text-end">{{ $row['days_worked'] }}d · {{ $row['hours_worked'] }}h @if ($row['late_days'])<div class="text-warning fs-9">{{ $row['late_days'] }} late</div>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-15">No staff activity in this period.</div>
                @endif
                <div class="text-muted fs-8 mt-4">
                    Rebooking: share of completed visits where the client booked again within {{ \App\Services\Reports\StaffPerformanceService::REBOOK_WINDOW_DAYS }} days.
                    Ratings come from the feedback link customers receive after their visit.
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
