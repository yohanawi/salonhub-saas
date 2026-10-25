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
                <form method="POST" action="{{ route('profile.leave.store') }}" class="card border-0 shadow-sm">
                    @csrf
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Request Leave</h3></div></div>
                    <div class="card-body pt-2">
                        @include('pages/apps.profile.partials._leave-fields')
                    </div>
                    <div class="card-footer"><button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Send Request</button></div>
                </form>
            </div>
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">My Requests</h3></div></div>
                    <div class="card-body pt-2">
                        @forelse ($requests as $leave)
                            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom border-gray-200 py-3 gap-3">
                                <div>
                                    <div class="fw-semibold text-gray-900">{{ str($leave->type)->replace('_', ' ')->headline() }}</div>
                                    <div class="text-muted fs-8">
                                        {{ $leave->start_datetime->format('D, M d') }}@unless ($leave->start_datetime->isSameDay($leave->end_datetime)) – {{ $leave->end_datetime->format('D, M d') }}@endunless
                                        · {{ rtrim(rtrim(number_format($days[$leave->id], 1), '0'), '.') }} {{ $days[$leave->id] == 1 ? 'day' : 'days' }}
                                        @if ($leave->review_notes) · “{{ $leave->review_notes }}” @endif
                                    </div>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge badge-light-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$leave->status] ?? 'secondary' }}">{{ ucfirst($leave->status) }}</span>
                                    @if ($leave->status === 'pending' || ($leave->status === 'approved' && $leave->start_datetime->isFuture()))
                                        <form method="POST" action="{{ route('profile.leave.cancel', $leave) }}" onsubmit="return confirm('Cancel this leave?');">@csrf<button class="btn btn-sm btn-light">Cancel</button></form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-muted text-center py-10">No leave requests yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-default-layout>
