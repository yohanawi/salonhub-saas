<x-default-layout>
    @section('title') Scheduled Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('reports.schedules.index') }} @endsection

    @php $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday']; @endphp

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <div class="row g-5">
            <div class="col-xl-4">
                <form method="POST" action="{{ route('reports.schedules.store') }}" class="card border-0 shadow-sm">
                    @csrf
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <h3 class="fw-bold text-gray-900 mb-1"><i class="bi bi-envelope-paper text-primary me-2"></i>New Scheduled Email</h3>
                            <div class="text-muted fs-8">Reports arrive by email with the file attached.</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="mb-4">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-solid" placeholder="e.g. Monday P&L for owners" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label required">Report</label>
                            <select name="report" class="form-select form-select-solid">
                                @foreach ($reports as $key => $label)
                                    <option value="{{ $key }}" @selected(old('report') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label required">Frequency</label>
                                <select name="frequency" id="schedule_frequency" class="form-select form-select-solid">
                                    @foreach ($frequencies as $key => $label)
                                        <option value="{{ $key }}" @selected(old('frequency', 'weekly') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label required">Time</label>
                                <input type="time" name="send_time" value="{{ old('send_time', '08:00') }}" class="form-control form-control-solid">
                            </div>
                            <div class="col-12 schedule-weekly">
                                <label class="form-label">Day</label>
                                <select name="day_of_week" class="form-select form-select-solid">
                                    @foreach ($days as $number => $day)
                                        <option value="{{ $number }}" @selected((int) old('day_of_week', 1) === $number)>{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 schedule-monthly">
                                <label class="form-label">Day of Month</label>
                                <input type="number" min="1" max="31" name="day_of_month" value="{{ old('day_of_month', 1) }}" class="form-control form-control-solid">
                            </div>
                        </div>
                        <div class="text-muted fs-8 mb-4">Daily emails cover yesterday, weekly the previous Mon–Sun, monthly the previous month.</div>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select form-select-solid">
                                    <option value="">All branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label required">Format</label>
                                <select name="format" class="form-select form-select-solid">
                                    <option value="pdf" @selected(old('format') === 'pdf')>PDF</option>
                                    <option value="xlsx" @selected(old('format') === 'xlsx')>Excel</option>
                                    <option value="csv" @selected(old('format') === 'csv')>CSV</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label required">Send To</label>
                            <textarea name="recipients_text" rows="2" class="form-control form-control-solid" placeholder="owner@salon.lk, manager@salon.lk">{{ old('recipients_text', auth()->user()->email) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary w-100"><i class="bi bi-calendar-plus me-1"></i>Schedule</button></div>
                </form>
            </div>

            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Schedules</h3></div></div>
                    <div class="card-body pt-2">
                        @forelse ($schedules as $schedule)
                            <div class="border border-gray-200 rounded-3 p-4 mb-3 {{ $schedule->is_active ? '' : 'opacity-50' }}">
                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                    <div>
                                        <div class="fw-bold text-gray-900">{{ $schedule->name }}</div>
                                        <div class="text-muted fs-8">
                                            {{ \App\Services\Reports\ReportCatalog::SCHEDULABLE[$schedule->report] ?? $schedule->report }}
                                            · {{ $schedule->schedule_label }} · {{ strtoupper($schedule->format) }}
                                            · {{ $schedule->branch?->name ?? 'All branches' }}
                                        </div>
                                        <div class="text-muted fs-8">To: {{ implode(', ', $schedule->recipients) }}</div>
                                        <div class="fs-8 mt-1">
                                            @if ($schedule->is_active)
                                                <span class="badge badge-light-success">Next: {{ $schedule->next_run_at?->format('D, M d H:i') }}</span>
                                            @else
                                                <span class="badge badge-light-secondary">Paused</span>
                                            @endif
                                            @if ($schedule->last_sent_at)
                                                <span class="badge badge-light">Last sent {{ $schedule->last_sent_at->format('M d H:i') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 align-items-start">
                                        <form method="POST" action="{{ route('reports.schedules.send', $schedule) }}">@csrf<button class="btn btn-sm btn-light-primary" title="Send now"><i class="bi bi-send"></i></button></form>
                                        <form method="POST" action="{{ route('reports.schedules.toggle', $schedule) }}">@csrf<button class="btn btn-sm btn-light">{{ $schedule->is_active ? 'Pause' : 'Resume' }}</button></form>
                                        <form method="POST" action="{{ route('reports.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule?');">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-light-danger"><i class="bi bi-trash"></i></button></form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-15">No scheduled reports yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const frequency = document.getElementById('schedule_frequency');
                const sync = () => {
                    document.querySelectorAll('.schedule-weekly').forEach((el) => el.classList.toggle('d-none', frequency.value !== 'weekly'));
                    document.querySelectorAll('.schedule-monthly').forEach((el) => el.classList.toggle('d-none', frequency.value !== 'monthly'));
                };
                frequency.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
</x-default-layout>
