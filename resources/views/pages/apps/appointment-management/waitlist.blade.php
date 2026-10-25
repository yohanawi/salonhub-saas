<x-default-layout>
    @section('title') Waitlist @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('appointment-management.waitlist.index') }} @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <div class="row g-5">
            {{-- Add to waitlist --}}
            @can('create', \App\Models\WaitlistEntry::class)
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold text-gray-900 mb-1">Add to Waitlist</h3>
                                <div class="text-muted fs-8">When a matching appointment is cancelled, this customer is offered the slot.</div>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <form method="POST" action="{{ route('appointment-management.waitlist.store') }}">
                                @csrf
                                <div class="mb-4">
                                    <label class="required form-label">Branch</label>
                                    <select name="branch_id" class="form-select form-select-solid" required>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="required form-label">Customer</label>
                                    <select name="customer_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select a customer" required>
                                        <option></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->full_name }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="required form-label">Service</label>
                                    <select name="service_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select a service" required>
                                        <option></option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Preferred staff</label>
                                    <select name="staff_id" class="form-select form-select-solid">
                                        <option value="">Anyone</option>
                                        @foreach ($staff as $member)
                                            <option value="{{ $member->id }}" @selected((string) old('staff_id') === (string) $member->id)>{{ $member->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="required form-label">Date</label>
                                    <input type="date" name="preferred_date" class="form-control form-control-solid" required
                                        min="{{ now()->toDateString() }}" value="{{ old('preferred_date', request('date')) }}">
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <label class="form-label">From</label>
                                        <input type="time" name="preferred_from" class="form-control form-control-solid" value="{{ old('preferred_from') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">To</label>
                                        <input type="time" name="preferred_to" class="form-control form-control-solid" value="{{ old('preferred_to') }}">
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" rows="2" class="form-control form-control-solid">{{ old('notes') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    Add to Waitlist
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Entries --}}
            <div class="col-xl">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="fw-bold text-gray-900 mb-0">Waiting Customers</h3>
                        </div>
                        <div class="card-toolbar">
                            <form method="GET" class="d-flex gap-2">
                                <input type="date" name="date" class="form-control form-control-sm form-control-solid" value="{{ request('date') }}">
                                <select name="status" class="form-select form-select-sm form-select-solid">
                                    @foreach (\App\Models\WaitlistEntry::STATUSES as $option)
                                        <option value="{{ $option }}" @selected($status === $option)>{{ str($option)->headline() }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-light-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase fs-8">
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Day & Time</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($entries as $entry)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-gray-900">{{ $entry->customer?->full_name }}</div>
                                                <div class="text-muted fs-8">{{ $entry->customer?->phone }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $entry->service?->name }}</div>
                                                <div class="text-muted fs-8">{{ $entry->staff?->full_name ?? 'Any staff' }} · {{ $entry->branch?->name }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $entry->preferred_date->format('D, d M Y') }}</div>
                                                <div class="text-muted fs-8">{{ $entry->timeWindowLabel() }}</div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light-{{ ['waiting' => 'warning', 'booked' => 'success', 'cancelled' => 'secondary', 'expired' => 'dark'][$entry->status] ?? 'secondary' }}">
                                                    {{ str($entry->status)->headline() }}
                                                </span>
                                                @if ($entry->appointment)
                                                    <a href="{{ route('appointment-management.appointments.show', $entry->appointment) }}" class="d-block fs-8 mt-1">{{ $entry->appointment->appointment_number }}</a>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($entry->status === \App\Models\WaitlistEntry::STATUS_WAITING)
                                                    @can('update', $entry)
                                                        <a href="{{ route('appointment-management.appointments.calendar', ['date' => $entry->preferred_date->format('Y-m-d'), 'branch_id' => $entry->branch_id]) }}"
                                                            class="btn btn-sm btn-light">Calendar</a>
                                                        <form method="POST" action="{{ route('appointment-management.waitlist.cancel', $entry) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-light-danger">Remove</button>
                                                        </form>
                                                    @endcan
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-10">Nobody is on the waitlist.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $entries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
