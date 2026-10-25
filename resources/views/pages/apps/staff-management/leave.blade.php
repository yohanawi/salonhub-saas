<x-default-layout>
    @section('title') Leave Requests @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('staff-management.leave.index') }} @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <div class="row g-5">
            <div class="col-xl-4">
                <form method="POST" action="{{ route('staff-management.leave.store') }}" class="card border-0 shadow-sm">
                    @csrf
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <h3 class="fw-bold mb-1"><i class="bi bi-airplane text-primary me-2"></i>Record Leave</h3>
                            <div class="text-muted fs-8">For leave agreed in person. It is approved straight away.</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="mb-4">
                            <label class="form-label required">Staff</label>
                            <select name="staff_id" class="form-select form-select-solid" required>
                                <option value="">Select staff</option>
                                @foreach ($staffMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('pages/apps.profile.partials._leave-fields')
                    </div>
                    <div class="card-footer"><button class="btn btn-primary w-100">Record Leave</button></div>
                </form>
            </div>

            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold mb-0">Requests @if ($pendingCount)<span class="badge badge-warning ms-2">{{ $pendingCount }} pending</span>@endif</h3></div>
                        <div class="card-toolbar">
                            <div class="btn-group btn-group-sm">
                                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
                                    <a href="{{ route('staff-management.leave.index', ['status' => $key]) }}" class="btn {{ $status === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @forelse ($requests as $leave)
                            <div class="border border-gray-200 rounded-3 p-4 mb-3">
                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                    <div>
                                        <div class="fw-bold text-gray-900">{{ $leave->staff?->full_name }} · {{ str($leave->type)->replace('_', ' ')->headline() }}</div>
                                        <div class="text-gray-700 fs-7">
                                            {{ $leave->start_datetime->format('D, M d') }}
                                            @unless ($leave->start_datetime->isSameDay($leave->end_datetime)) – {{ $leave->end_datetime->format('D, M d') }} @endunless
                                            <span class="text-muted">({{ rtrim(rtrim(number_format($days[$leave->id], 1), '0'), '.') }} {{ $days[$leave->id] == 1 ? 'day' : 'days' }})</span>
                                        </div>
                                        @if ($leave->reason)<div class="text-muted fs-8">“{{ $leave->reason }}”</div>@endif
                                        @if ($leave->review_notes)<div class="text-muted fs-8">Note: {{ $leave->review_notes }}</div>@endif
                                        @if (($clashes[$leave->id] ?? 0) > 0)
                                            <div class="text-danger fs-8 mt-1"><i class="bi bi-exclamation-triangle"></i> {{ $clashes[$leave->id] }} booked {{ str('appointment')->plural($clashes[$leave->id]) }} during this time. Move them after approving.</div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-light-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$leave->status] ?? 'secondary' }}">{{ ucfirst($leave->status) }}</span>
                                        @if ($leave->status === 'pending')
                                            <form method="POST" action="{{ route('staff-management.leave.review', $leave) }}" class="d-flex gap-2 mt-3">
                                                @csrf
                                                <input type="text" name="review_notes" class="form-control form-control-solid form-control-sm w-150px" placeholder="Note (optional)">
                                                <button name="decision" value="approve" class="btn btn-sm btn-light-success">Approve</button>
                                                <button name="decision" value="reject" class="btn btn-sm btn-light-danger">Reject</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-15">No {{ $status === 'all' ? '' : $status }} leave requests.</div>
                        @endforelse
                        <div class="mt-4">{{ $requests->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
