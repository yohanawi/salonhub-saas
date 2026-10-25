<x-default-layout>
    @section('title') Shift Roster @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('staff-management.roster.index') }} @endsection

    <style>
        .roster-cell { min-width: 120px; height: 64px; vertical-align: middle; cursor: pointer; }
        .roster-cell.drag-over { outline: 2px dashed var(--bs-primary); background: var(--bs-light-primary); }
        .roster-chip { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: grab; user-select: none; }
        .roster-chip.shift { background: var(--bs-light-primary); color: var(--bs-primary); }
        .roster-chip.weekly { background: var(--bs-gray-100); color: var(--bs-gray-700); border: 1px dashed var(--bs-gray-400); }
        .roster-chip.off { background: var(--bs-light-danger); color: var(--bs-danger); cursor: default; }
        .roster-chip.elsewhere { background: var(--bs-light-warning); color: var(--bs-warning); cursor: default; }
        .roster-today { background: var(--bs-light-success) !important; }
    </style>

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif
        <div id="roster_error" class="alert alert-danger border-0 shadow-sm mb-5 d-none"></div>

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Shift Roster · {{ $branch->name }}</h3>
                    <div class="text-muted fs-7">Week of {{ $weekStart->format('M d') }} – {{ $weekStart->addDays(6)->format('M d, Y') }}. Drag a shift to move it, click a cell to edit. Dashed shifts come from the weekly schedule.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <select name="branch_id" class="form-select form-select-solid form-select-sm w-175px" onchange="this.form.submit()">
                            @foreach ($branches as $option)
                                <option value="{{ $option->id }}" @selected($option->id === $branch->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('staff-management.roster.index', ['branch_id' => $branch->id, 'week' => $weekStart->subWeek()->toDateString()]) }}" class="btn btn-sm btn-light"><i class="bi bi-chevron-left"></i></a>
                    <a href="{{ route('staff-management.roster.index', ['branch_id' => $branch->id]) }}" class="btn btn-sm btn-light">This week</a>
                    <a href="{{ route('staff-management.roster.index', ['branch_id' => $branch->id, 'week' => $weekStart->addWeek()->toDateString()]) }}" class="btn btn-sm btn-light"><i class="bi bi-chevron-right"></i></a>
                    <form method="POST" action="{{ route('staff-management.roster.copy', ['branch_id' => $branch->id, 'week' => $weekStart->toDateString()]) }}" onsubmit="return confirm('Copy last week\'s shifts onto this week?');">
                        @csrf
                        <button class="btn btn-sm btn-light-primary"><i class="bi bi-files me-1"></i>Copy last week</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if ($staff->isEmpty())
                    <div class="text-center text-muted py-15">No staff are assigned to {{ $branch->name }}.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center fs-7 mb-0">
                            <thead>
                                <tr class="fw-bold text-gray-700 bg-light">
                                    <th class="text-start min-w-175px">Staff</th>
                                    @foreach ($days as $day)
                                        <th class="{{ $day->isToday() ? 'roster-today' : '' }}">{{ $day->format('D') }}<div class="text-muted fs-8 fw-normal">{{ $day->format('M d') }}</div></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($staff as $member)
                                    <tr>
                                        <td class="text-start"><div class="fw-semibold text-gray-900">{{ $member->full_name }}</div><div class="text-muted fs-8">{{ $member->job_title }}</div></td>
                                        @foreach ($days as $day)
                                            @php $cell = $cells[$member->id][$day->toDateString()]; @endphp
                                            <td class="roster-cell" data-staff="{{ $member->id }}" data-date="{{ $day->toDateString() }}" data-start="{{ $cell['start'] }}" data-end="{{ $cell['end'] }}" data-kind="{{ $cell['kind'] }}" data-name="{{ $member->full_name }}">
                                                @if ($cell['kind'] !== 'none')
                                                    <span class="roster-chip {{ $cell['kind'] }}" draggable="{{ in_array($cell['kind'], ['shift', 'weekly'], true) ? 'true' : 'false' }}">{{ $cell['label'] }}</span>
                                                @endif
                                                @if ($cell['leave'])
                                                    <div class="text-info fs-9 mt-1"><i class="bi bi-airplane"></i> {{ $cell['leave'] }}</div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="roster_modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="roster_modal_title">Edit shift</h5></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label fs-8">Start</label><input type="time" id="roster_start" class="form-control form-control-solid"></div>
                        <div class="col-6"><label class="form-label fs-8">End</label><input type="time" id="roster_end" class="form-control form-control-solid"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light-danger" data-roster-action="off">Day off</button>
                        <button type="button" class="btn btn-sm btn-light" data-roster-action="clear" title="Use the weekly schedule">Reset</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-roster-action="set">Save</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const branchId = {{ $branch->id }};
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                const errorBox = document.getElementById('roster_error');
                const modalEl = document.getElementById('roster_modal');
                const modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;
                let current = null;
                let dragged = null;

                async function post(url, body) {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify(Object.assign({ branch_id: branchId }, body)),
                    });

                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        const message = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Could not update the roster.');
                        errorBox.textContent = message;
                        errorBox.classList.remove('d-none');
                        return false;
                    }

                    window.location.reload();
                    return true;
                }

                document.querySelectorAll('.roster-cell').forEach(function(cell) {
                    cell.addEventListener('click', function() {
                        if (['elsewhere'].includes(cell.dataset.kind)) {
                            return;
                        }
                        current = cell;
                        document.getElementById('roster_modal_title').textContent = cell.dataset.name + ' · ' + cell.dataset.date;
                        document.getElementById('roster_start').value = cell.dataset.start || '09:00';
                        document.getElementById('roster_end').value = cell.dataset.end || '17:00';
                        modal?.show();
                    });

                    cell.addEventListener('dragstart', function(event) {
                        if (!event.target.matches('.roster-chip[draggable="true"]')) {
                            return;
                        }
                        dragged = cell;
                        event.dataTransfer.effectAllowed = 'move';
                    });
                    cell.addEventListener('dragover', function(event) {
                        if (dragged) {
                            event.preventDefault();
                            cell.classList.add('drag-over');
                        }
                    });
                    cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));
                    cell.addEventListener('drop', function(event) {
                        event.preventDefault();
                        cell.classList.remove('drag-over');

                        if (!dragged || dragged === cell) {
                            dragged = null;
                            return;
                        }

                        post('{{ route('staff-management.roster.move') }}', {
                            from_staff_id: dragged.dataset.staff,
                            from_date: dragged.dataset.date,
                            to_staff_id: cell.dataset.staff,
                            to_date: cell.dataset.date,
                        });
                        dragged = null;
                    });
                });

                modalEl.querySelectorAll('[data-roster-action]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        if (!current) {
                            return;
                        }
                        modal?.hide();
                        post('{{ route('staff-management.roster.save') }}', {
                            staff_id: current.dataset.staff,
                            date: current.dataset.date,
                            action: button.dataset.rosterAction,
                            start_time: document.getElementById('roster_start').value,
                            end_time: document.getElementById('roster_end').value,
                        });
                    });
                });
            });
        </script>
    @endpush
</x-default-layout>
