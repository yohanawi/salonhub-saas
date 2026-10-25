{{-- ============================================================
    TIMELINE: staff columns on a time grid. Pending and confirmed appointments can be dragged to another time or
    staff member, and stretched from their bottom edge (AppointmentRescheduleController).
============================================================ --}}
@php
    $pxPerMinute = 1.4;

    $dayStartMinute = 8 * 60;
    $dayEndMinute = 20 * 60;

    foreach ($appointments as $timelineAppointment) {
        if ($timelineAppointment->starts_at) {
            $dayStartMinute = min($dayStartMinute, $timelineAppointment->starts_at->hour * 60);
        }
        if ($timelineAppointment->ends_at) {
            $endsOnDay = $timelineAppointment->ends_at->isSameDay($date) ? $timelineAppointment->ends_at->hour * 60 + 60 : 24 * 60;
            $dayEndMinute = max($dayEndMinute, min(24 * 60, $endsOnDay));
        }
    }

    $timelineHeight = ($dayEndMinute - $dayStartMinute) * $pxPerMinute;
    $minutesOf = fn ($time) => $time->hour * 60 + $time->minute;

    $timelineStatusClass = [
        'pending' => 'warning',
        'confirmed' => 'primary',
        'checked_in' => 'info',
        'in_progress' => 'success',
        'completed' => 'success',
        'cancelled' => 'danger',
        'no_show' => 'dark',
    ];
@endphp

@push('styles')
    <style>
        .tl-scroll { overflow-x: auto; }
        .tl-grid { display: flex; min-width: max-content; }
        .tl-gutter { width: 64px; flex-shrink: 0; }
        .tl-col { width: 220px; flex-shrink: 0; border-left: 1px solid var(--bs-gray-200); }
        .tl-head { height: 56px; position: sticky; top: 0; z-index: 3; background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-gray-200); padding: 8px 12px; }
        .tl-col-body { position: relative;
            background-image: repeating-linear-gradient(to bottom, var(--bs-gray-200) 0, var(--bs-gray-200) 1px,
                transparent 1px, transparent {{ 60 * $pxPerMinute }}px); }
        .tl-hour { position: absolute; right: 8px; transform: translateY(-50%); font-size: 11px; color: var(--bs-gray-500); }
        .tl-event { position: absolute; left: 6px; right: 6px; border-radius: 8px; padding: 6px 8px; overflow: hidden;
            font-size: 12px; line-height: 1.3; border-left: 4px solid; box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
            user-select: none; touch-action: none; text-decoration: none; display: block; }
        .tl-event[data-editable="1"] { cursor: grab; }
        .tl-event.tl-dragging { cursor: grabbing; opacity: .85; z-index: 5; box-shadow: 0 6px 16px rgba(0, 0, 0, .18); }
        .tl-event.tl-saving { opacity: .5; pointer-events: none; }
        .tl-resize { position: absolute; left: 0; right: 0; bottom: 0; height: 8px; cursor: ns-resize; }
        .tl-resize::after { content: ''; position: absolute; left: 50%; bottom: 2px; width: 24px; height: 3px;
            margin-left: -12px; border-radius: 2px; background: currentColor; opacity: .35; }
        .tl-now { position: absolute; left: 0; right: 0; height: 2px; background: var(--bs-danger); z-index: 4; }
    </style>
@endpush

<div class="card calendar-board-card border-0 shadow-sm">

    <div class="card-header border-0 py-7">
        <div class="card-title">
            <div class="d-flex align-items-center gap-4">
                <div class="calendar-board-icon">
                    <i class="bi bi-clock text-primary fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Timeline</h3>
                    <div class="text-muted fs-7">
                        Drag a pending or confirmed appointment to move it to another time or staff member. Drag its
                        bottom edge to change how long it takes. Times snap to {{ $snapMinutes }} minutes.
                    </div>
                </div>
            </div>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('appointment-management.appointments.calendar', array_merge(request()->query(), ['view' => 'cards'])) }}"
                class="btn btn-sm btn-light">
                <i class="bi bi-columns-gap me-1"></i>
                Cards
            </a>
        </div>
    </div>

    <div class="card-body pt-0">
        @if ($staffColumns->isEmpty())
            <div class="text-center text-muted py-15">No bookable staff for the selected filters.</div>
        @else
            <div class="tl-scroll">
                <div class="tl-grid" id="appointment-timeline"
                    data-date="{{ $date->format('Y-m-d') }}"
                    data-day-start="{{ $dayStartMinute }}"
                    data-px-per-minute="{{ $pxPerMinute }}"
                    data-snap="{{ $snapMinutes }}">

                    {{-- Hours --}}
                    <div class="tl-gutter">
                        <div class="tl-head"></div>
                        <div class="position-relative" style="height: {{ $timelineHeight }}px">
                            @for ($minute = $dayStartMinute; $minute < $dayEndMinute; $minute += 60)
                                @if ($minute > $dayStartMinute)
                                    <span class="tl-hour" style="top: {{ ($minute - $dayStartMinute) * $pxPerMinute }}px">
                                        {{ \Carbon\Carbon::createFromTime(intdiv($minute, 60))->format('g A') }}
                                    </span>
                                @endif
                            @endfor
                        </div>
                    </div>

                    @foreach ($staffColumns as $member)
                        <div class="tl-col">
                            <div class="tl-head">
                                <div class="fw-bold text-gray-900 text-truncate">{{ $member->full_name }}</div>
                                <div class="text-muted fs-8 text-truncate">{{ $member->job_title ?: 'Bookable Staff' }}</div>
                            </div>
                            <div class="tl-col-body" data-staff-id="{{ $member->id }}" style="height: {{ $timelineHeight }}px">
                                @if ($date->isToday())
                                    @php $nowMinute = $minutesOf(now($member->tenant?->timezone ?: config('app.timezone'))); @endphp
                                    @if ($nowMinute >= $dayStartMinute && $nowMinute <= $dayEndMinute)
                                        <div class="tl-now" style="top: {{ ($nowMinute - $dayStartMinute) * $pxPerMinute }}px"></div>
                                    @endif
                                @endif

                                @foreach ($appointments as $appointment)
                                    @php
                                        $memberLines = $appointment->appointmentServices
                                            ->where('staff_id', $member->id)
                                            ->whereNotIn('status', ['cancelled', 'no_show']);
                                    @endphp
                                    @continue($memberLines->isEmpty() || ! $appointment->starts_at)
                                    @php
                                        $blockStart = $memberLines->min('starts_at') ?? $appointment->starts_at;
                                        $blockEnd = $memberLines->max('ends_at') ?? $appointment->ends_at;
                                        $startMinute = $blockStart->isSameDay($date) ? $minutesOf($blockStart) : $dayStartMinute;
                                        $endMinute = $blockEnd->isSameDay($date) ? $minutesOf($blockEnd) : $dayEndMinute;
                                        $editable = in_array($appointment->status, ['pending', 'confirmed'], true)
                                            && auth()->user()->can('update', $appointment);
                                        $color = $timelineStatusClass[$appointment->status] ?? 'secondary';
                                    @endphp
                                    <a href="{{ route('appointment-management.appointments.show', $appointment) }}"
                                        class="tl-event bg-light-{{ $color }} border-{{ $color }} text-gray-800"
                                        style="top: {{ ($startMinute - $dayStartMinute) * $pxPerMinute }}px; height: {{ max(20, ($endMinute - $startMinute) * $pxPerMinute) }}px"
                                        data-editable="{{ $editable ? 1 : 0 }}"
                                        data-staff-id="{{ $member->id }}"
                                        data-appointment-start="{{ $minutesOf($appointment->starts_at) }}"
                                        data-move-url="{{ route('appointment-management.appointments.move', $appointment) }}"
                                        data-resize-url="{{ route('appointment-management.appointments.resize', $appointment) }}">
                                        <div class="fw-bold tl-time">{{ $blockStart->format('h:i A') }} – {{ $blockEnd->format('h:i A') }}</div>
                                        <div class="fw-semibold text-truncate">{{ $appointment->customer?->full_name ?: 'Walk-in Customer' }}</div>
                                        <div class="text-muted text-truncate">{{ $memberLines->pluck('service_name')->filter()->implode(', ') }}</div>
                                        @if ($editable)
                                            <span class="tl-resize" title="Drag to change length"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        @endif
    </div>

</div>

@push('scripts')
    <script>
        (() => {
            const grid = document.getElementById('appointment-timeline');

            if (!grid) {
                return;
            }

            const pxPerMinute = parseFloat(grid.dataset.pxPerMinute);
            const dayStart = parseInt(grid.dataset.dayStart, 10);
            const snap = parseInt(grid.dataset.snap, 10);
            const date = grid.dataset.date;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const pad = (n) => String(n).padStart(2, '0');
            const clock = (minute) => `${pad(Math.floor(minute / 60))}:${pad(minute % 60)}`;
            const label = (minute) => {
                const h = Math.floor(minute / 60);
                return `${pad(((h + 11) % 12) + 1)}:${pad(minute % 60)} ${h < 12 || h === 24 ? 'AM' : 'PM'}`;
            };
            const snapped = (px) => Math.round(px / pxPerMinute / snap) * snap;

            const fail = (message) => {
                if (window.Swal) {
                    Swal.fire({ text: message, icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok', customClass: { confirmButton: 'btn btn-primary' } });
                } else {
                    alert(message);
                }
            };

            const save = async (url, body) => {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                    throw new Error(firstError || data.message || 'The appointment could not be changed.');
                }

                return data;
            };

            grid.querySelectorAll('.tl-event').forEach((event) => {
                // A plain click still opens the appointment; only a real drag reschedules it.
                event.addEventListener('click', (e) => {
                    if (event.dataset.dragged === '1') {
                        e.preventDefault();
                        event.dataset.dragged = '0';
                    }
                });

                if (event.dataset.editable !== '1') {
                    return;
                }

                event.addEventListener('pointerdown', (down) => {
                    if (down.button !== 0) {
                        return;
                    }

                    const resizing = !!down.target.closest('.tl-resize');
                    const origin = { top: event.offsetTop, height: event.offsetHeight, column: event.parentElement };
                    const timeLabel = event.querySelector('.tl-time');
                    const originalLabel = timeLabel.textContent;
                    let column = origin.column;
                    let moved = false;

                    event.setPointerCapture(down.pointerId);

                    const onMove = (move) => {
                        const dy = move.clientY - down.clientY;

                        if (!moved && Math.abs(dy) < 4 && Math.abs(move.clientX - down.clientX) < 4) {
                            return;
                        }

                        moved = true;
                        event.classList.add('tl-dragging');

                        if (resizing) {
                            const minutes = Math.max(snap, snapped(origin.height + dy));
                            event.style.height = `${minutes * pxPerMinute}px`;
                            const start = dayStart + Math.round(origin.top / pxPerMinute);
                            timeLabel.textContent = `${label(start)} – ${label(start + minutes)}`;
                            return;
                        }

                        const top = Math.max(0, snapped(origin.top + dy));
                        event.style.top = `${top * pxPerMinute}px`;
                        const duration = Math.round(origin.height / pxPerMinute);
                        timeLabel.textContent = `${label(dayStart + top)} – ${label(dayStart + top + duration)}`;

                        event.style.pointerEvents = 'none';
                        const under = document.elementFromPoint(move.clientX, move.clientY)?.closest('.tl-col-body');
                        event.style.pointerEvents = '';

                        if (under && under !== column) {
                            under.appendChild(event);
                            column = under;
                        }
                    };

                    const revert = () => {
                        origin.column.appendChild(event);
                        event.style.top = `${origin.top}px`;
                        event.style.height = `${origin.height}px`;
                        timeLabel.textContent = originalLabel;
                    };

                    const onUp = async () => {
                        event.removeEventListener('pointermove', onMove);
                        event.removeEventListener('pointerup', onUp);
                        event.removeEventListener('pointercancel', onCancel);
                        event.classList.remove('tl-dragging');

                        if (!moved) {
                            return;
                        }

                        event.dataset.dragged = '1';
                        event.classList.add('tl-saving');

                        const topMinute = Math.round(event.offsetTop / pxPerMinute);

                        try {
                            if (resizing) {
                                const end = dayStart + topMinute + Math.round(event.offsetHeight / pxPerMinute);
                                await save(event.dataset.resizeUrl, {
                                    staff_id: event.dataset.staffId,
                                    ends_at: `${date} ${clock(end)}`,
                                });
                            } else {
                                // The block may start after the appointment (a later service); shift the whole appointment.
                                const shift = topMinute - Math.round(origin.top / pxPerMinute);
                                const start = parseInt(event.dataset.appointmentStart, 10) + shift;
                                await save(event.dataset.moveUrl, {
                                    starts_at: `${date} ${clock(start)}`,
                                    from_staff_id: event.dataset.staffId,
                                    to_staff_id: column.dataset.staffId,
                                });
                            }

                            window.location.reload();
                        } catch (error) {
                            event.classList.remove('tl-saving');
                            revert();
                            fail(error.message);
                        }
                    };

                    const onCancel = () => {
                        event.removeEventListener('pointermove', onMove);
                        event.removeEventListener('pointerup', onUp);
                        event.removeEventListener('pointercancel', onCancel);
                        event.classList.remove('tl-dragging');
                        revert();
                    };

                    event.addEventListener('pointermove', onMove);
                    event.addEventListener('pointerup', onUp);
                    event.addEventListener('pointercancel', onCancel);
                });

                // Stops the browser's own link drag from taking over.
                event.addEventListener('dragstart', (e) => e.preventDefault());
            });
        })();
    </script>
@endpush
