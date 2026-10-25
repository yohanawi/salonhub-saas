<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    @if (! $staff)
        <div class="card">
            <div class="card-body text-muted fs-6 py-10 text-center">
                Your account isn't linked to a staff profile, so there are no appointments assigned to you.
            </div>
        </div>
    @else
        @foreach ([
            ['title' => 'Today', 'date' => $agenda['todayDate'], 'appointments' => $agenda['today'], 'empty' => 'No appointments assigned to you today.'],
            ['title' => 'Tomorrow', 'date' => $agenda['tomorrowDate'], 'appointments' => $agenda['tomorrow'], 'empty' => 'No appointments assigned to you tomorrow.'],
        ] as $day)
            <!--begin::Day-->
            <div class="card {{ $loop->last ? '' : 'mb-5 mb-xl-10' }}">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <h3 class="m-0 text-gray-800">{{ $day['title'] }}</h3>
                        <span class="text-muted fs-7 fw-semibold mt-1">{{ $day['date']->format('l, M d, Y') }}</span>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge badge-light-primary fs-7 px-3 py-2">
                            {{ $day['appointments']->count() }} {{ str('appointment')->plural($day['appointments']->count()) }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($day['appointments']->isEmpty())
                        <div class="text-muted fs-6 py-10 text-center">{{ $day['empty'] }}</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9">
                                <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                                    <tr>
                                        <th class="min-w-125px ps-9">Time</th>
                                        <th class="min-w-175px">Customer</th>
                                        <th class="min-w-200px">My Services</th>
                                        <th class="min-w-125px">Branch</th>
                                        <th class="min-w-100px text-center">Status</th>
                                        <th class="min-w-75px text-end pe-9"></th>
                                    </tr>
                                </thead>
                                <tbody class="fs-6 fw-semibold text-gray-600">
                                    @foreach ($day['appointments'] as $appointment)
                                        @php
                                            $myServices = $appointment->appointmentServices
                                                ->filter(fn ($line) => (int) $line->staff_id === (int) $staff->id || ($line->staff_id === null && (int) $appointment->staff_id === (int) $staff->id));

                                            $statusClass = match ($appointment->status) {
                                                'pending' => 'warning',
                                                'confirmed' => 'primary',
                                                'checked_in' => 'info',
                                                'in_progress', 'completed' => 'success',
                                                'cancelled' => 'danger',
                                                'no_show' => 'dark',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="ps-9">
                                                <div class="text-gray-800 fw-bold">{{ $appointment->starts_at->format('h:i A') }}</div>
                                                @if ($appointment->ends_at)
                                                    <div class="text-muted fs-7">to {{ $appointment->ends_at->format('h:i A') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="text-gray-800">{{ $appointment->customer?->full_name ?? 'Walk-in customer' }}</div>
                                                @if ($appointment->customer?->phone)
                                                    <div class="text-muted fs-7">{{ $appointment->customer->phone }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse ($myServices as $line)
                                                    <div>
                                                        {{ $line->service_name ?? 'Service' }}
                                                        @if ($line->starts_at)
                                                            <span class="text-muted fs-7">({{ $line->starts_at->format('h:i A') }})</span>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <span class="text-muted">—</span>
                                                @endforelse
                                            </td>
                                            <td>{{ $appointment->branch?->name ?? '—' }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-light-{{ $statusClass }}">{{ $appointment->status_label }}</span>
                                            </td>
                                            <td class="text-end pe-9">
                                                @can('view', $appointment)
                                                    <a href="{{ route('appointment-management.appointments.show', $appointment) }}"
                                                        class="btn btn-sm btn-light-primary">View</a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            <!--end::Day-->
        @endforeach
    @endif
</x-default-layout>
