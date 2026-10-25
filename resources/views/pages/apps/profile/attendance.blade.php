<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
    @endif

    @if (! $staff)
        <div class="card"><div class="card-body text-muted text-center py-10">Your account isn't linked to a staff profile.</div></div>
    @else
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-10">
                        <div class="text-muted fs-7 mb-1">{{ $now->format('l, M d') }}</div>
                        <div class="fs-2hx fw-bolder text-gray-900 mb-6" id="attendance_clock">{{ $now->format('H:i') }}</div>

                        @if (! $today?->clock_in_at)
                            <form method="POST" action="{{ route('profile.attendance.clock-in') }}">
                                @csrf
                                <button class="btn btn-success btn-lg w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Clock In</button>
                            </form>
                        @elseif ($today->isOpen())
                            <div class="mb-4 text-gray-700">Clocked in at <strong>{{ $today->clock_in_at->format('H:i') }}</strong> · {{ $today->worked_label }} so far</div>
                            <form method="POST" action="{{ route('profile.attendance.clock-out') }}">
                                @csrf
                                <div class="d-flex align-items-center gap-2 justify-content-center mb-4">
                                    <label class="text-muted fs-7">Break taken</label>
                                    <input type="number" name="break_minutes" min="0" value="0" class="form-control form-control-solid form-control-sm w-80px">
                                    <span class="text-muted fs-7">min</span>
                                </div>
                                <button class="btn btn-danger btn-lg w-100"><i class="bi bi-box-arrow-right me-2"></i>Clock Out</button>
                            </form>
                        @else
                            <div class="text-gray-700">Done for today: {{ $today->clock_in_at->format('H:i') }}–{{ $today->clock_out_at->format('H:i') }} · <strong>{{ $today->worked_label }}</strong></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Recent Days</h3></div></div>
                    <div class="card-body pt-2">
                        @if ($history->isNotEmpty())
                            <table class="table align-middle table-row-dashed gy-3 fs-7">
                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Date</th><th>Branch</th><th>In</th><th>Out</th><th>Worked</th><th class="text-center">Status</th></tr></thead>
                                <tbody>
                                    @foreach ($history as $record)
                                        <tr>
                                            <td>{{ $record->work_date->format('D, M d') }}</td>
                                            <td>{{ $record->branch?->name }}</td>
                                            <td>{{ $record->clock_in_at?->format('H:i') ?? '—' }}</td>
                                            <td>{{ $record->clock_out_at?->format('H:i') ?? '—' }}</td>
                                            <td>{{ $record->worked_label }}</td>
                                            <td class="text-center"><span class="badge badge-light-{{ $record->status_color }}">{{ ucfirst($record->status) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-muted text-center py-10">No attendance yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-default-layout>
