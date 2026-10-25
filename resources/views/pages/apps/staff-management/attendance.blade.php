<x-default-layout>
    @section('title') Attendance @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('staff-management.attendance.index') }} @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Attendance · {{ $branch->name }}</h3>
                    <div class="text-muted fs-7">{{ $date->format('l, M d, Y') }}. Late = more than {{ \App\Services\Staff\AttendanceService::GRACE_MINUTES }} minutes after the rostered start.</div>
                </div>
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                    <select name="branch_id" class="form-select form-select-solid form-select-sm w-175px">
                        @foreach ($branches as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $branch->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control form-control-solid form-control-sm w-150px">
                    <button class="btn btn-sm btn-light-primary">Show</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Daily Board</h3></div></div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-3 fs-7">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th class="min-w-150px">Staff</th>
                                <th>Rostered</th>
                                <th>In</th>
                                <th>Out</th>
                                <th>Worked</th>
                                <th class="text-center">Status</th>
                                <th class="min-w-400px">Record / correct</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($board as $row)
                                @php $record = $row['record']; @endphp
                                <tr>
                                    <td><div class="fw-semibold text-gray-900">{{ $row['staff']->full_name }}</div><div class="text-muted fs-8">{{ $row['staff']->job_title }}</div></td>
                                    <td>{{ $row['window'] ? substr($row['window'][0], 0, 5) . '–' . substr($row['window'][1], 0, 5) : 'Not rostered' }}</td>
                                    <td>{{ $record?->clock_in_at?->format('H:i') ?? '—' }}</td>
                                    <td>{{ $record?->clock_out_at?->format('H:i') ?? ($record?->isOpen() ? 'On shift' : '—') }}</td>
                                    <td>{{ $record ? $record->worked_label : '—' }}</td>
                                    <td class="text-center">
                                        @if ($record)
                                            <span class="badge badge-light-{{ $record->status_color }}">{{ ucfirst($record->status) }}{{ $record->late_minutes > \App\Services\Staff\AttendanceService::GRACE_MINUTES ? " +{$record->late_minutes}m" : '' }}</span>
                                        @elseif ($row['leave'])
                                            <span class="badge badge-light-info">On leave</span>
                                        @elseif ($row['window'])
                                            <span class="badge badge-light-danger">Not in</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('staff-management.attendance.store', ['branch_id' => $branch->id]) }}" class="d-flex flex-wrap gap-2 align-items-center">
                                            @csrf
                                            <input type="hidden" name="staff_id" value="{{ $row['staff']->id }}">
                                            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                                            <select name="status" class="form-select form-select-solid form-select-sm w-100px">
                                                @foreach (\App\Models\StaffAttendance::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected(($record?->status ?? ($row['leave'] ? 'leave' : 'present')) === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <input type="time" name="clock_in" value="{{ $record?->clock_in_at?->format('H:i') }}" class="form-control form-control-solid form-control-sm w-100px" title="In">
                                            <input type="time" name="clock_out" value="{{ $record?->clock_out_at?->format('H:i') }}" class="form-control form-control-solid form-control-sm w-100px" title="Out">
                                            <input type="number" name="break_minutes" min="0" value="{{ $record?->break_minutes ?? 0 }}" class="form-control form-control-solid form-control-sm w-70px" title="Break (min)">
                                            <button class="btn btn-sm btn-light-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <div class="card-title"><h3 class="fw-bold mb-0">Monthly Summary · {{ $month->format('F Y') }}</h3></div>
                <div class="card-toolbar d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control form-control-solid form-control-sm w-150px" onchange="this.form.submit()">
                    </form>
                    @include('partials.reports._export-buttons', ['route' => 'staff-management.attendance.index', 'params' => ['branch_id' => $branch->id, 'month' => $month->format('Y-m')]])
                </div>
            </div>
            <div class="card-body pt-2">
                @if ($summary->isNotEmpty())
                    <table class="table align-middle table-row-dashed gy-3 fs-7">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>Staff</th><th class="text-end">Days</th><th class="text-end">Late</th><th class="text-end">Absent</th><th class="text-end">Leave</th><th class="text-end">Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['staff']?->full_name }}</td>
                                    <td class="text-end">{{ $row['days'] }}</td>
                                    <td class="text-end">{{ $row['late'] }}@if ($row['late_minutes'])<span class="text-muted fs-8"> ({{ $row['late_minutes'] }}m)</span>@endif</td>
                                    <td class="text-end">{{ $row['absent'] }}</td>
                                    <td class="text-end">{{ $row['leave'] }}</td>
                                    <td class="text-end">{{ round($row['minutes'] / 60, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted fs-7 py-6 text-center">No attendance recorded this month.</div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
