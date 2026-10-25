{{-- ================================================= --}}
{{-- LOGIN ACCESS --}}
{{-- ================================================= --}}
@php
    $loginUser = $staffMember->user;
    $loginRole = $loginUser?->roles->first()?->name;
    $loginLocked = $loginUser && ($loginUser->is(auth()->user()) || $loginUser->hasAnyRole(['Super Admin', 'Salon Owner']));
@endphp

<div class="card border-0 shadow-sm mb-8">

    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="symbol symbol-45px me-4">
                <div class="symbol-label bg-light-danger">
                    <i class="bi bi-shield-lock text-danger fs-3"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold text-gray-900 mb-1">
                    Login Access
                </h3>
                <div class="text-muted fs-8">
                    Let this staff member sign in with their own account.
                </div>
            </div>
        </div>
    </div>

    <div class="card-body pt-4">

        @if ($errors->hasAny(['email', 'role', 'access']))
            <div class="alert alert-danger py-3 mb-6">
                {{ $errors->first('email') ?: ($errors->first('role') ?: $errors->first('access')) }}
            </div>
        @endif

        @if (! $loginUser)
            <form method="POST" action="{{ route('staff-management.staff.access.invite', $staffMember) }}">
                @csrf
                <div class="mb-5">
                    <label class="required form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control form-control-solid" required
                        value="{{ old('email', $staffMember->email) }}" placeholder="staff@example.com">
                </div>
                <div class="mb-6">
                    <label class="required form-label fw-semibold">Role</label>
                    <select name="role" class="form-select form-select-solid" required>
                        @foreach ($assignableRoles as $role)
                            <option value="{{ $role }}" @selected(old('role', 'Beautician') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <div class="text-muted fs-8 mt-2">
                        They get an email to choose a password. Uses one of your plan's user seats.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-envelope-paper me-1"></i>
                    Send Invitation
                </button>
            </form>
        @else
            <div class="d-flex align-items-center justify-content-between mb-5">
                <div class="overflow-hidden">
                    <div class="fw-semibold text-gray-900 text-truncate" title="{{ $loginUser->email }}">
                        {{ $loginUser->email }}
                    </div>
                    <div class="text-muted fs-8">{{ $loginRole ?? 'No role' }}</div>
                </div>
                @if ($loginUser->status !== 'active')
                    <span class="badge badge-light-danger">Login off</span>
                @elseif ($loginUser->hasPendingInvitation())
                    <span class="badge badge-light-warning">Invite pending</span>
                @else
                    <span class="badge badge-light-success">Active</span>
                @endif
            </div>

            @if ($loginUser->hasPendingInvitation())
                <div class="text-muted fs-8 mb-4">
                    Invited {{ $loginUser->invited_at->diffForHumans() }}. The link is valid for
                    {{ \App\Services\Staff\StaffAccessService::INVITATION_DAYS }} days.
                </div>
                <form method="POST" action="{{ route('staff-management.staff.access.resend', $staffMember) }}" class="mb-5">
                    @csrf
                    <button type="submit" class="btn btn-light-primary btn-sm w-100">
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Resend Invitation
                    </button>
                </form>
            @endif

            @if ($loginLocked)
                <div class="text-muted fs-8">
                    This login belongs to {{ $loginUser->is(auth()->user()) ? 'you' : 'the salon owner' }} and can't be changed here.
                </div>
            @else
                <form method="POST" action="{{ route('staff-management.staff.access.role', $staffMember) }}"
                    class="d-flex gap-2 mb-4">
                    @csrf
                    @method('PATCH')
                    <select name="role" class="form-select form-select-solid form-select-sm">
                        @foreach ($assignableRoles as $role)
                            <option value="{{ $role }}" @selected($loginRole === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-light-primary text-nowrap">Change Role</button>
                </form>

                <form method="POST" action="{{ route('staff-management.staff.access.status', $staffMember) }}"
                    data-swal-confirm
                    data-swal-title="{{ $loginUser->status === 'active' ? 'Turn off login access?' : 'Turn on login access?' }}"
                    data-swal-text="{{ $loginUser->status === 'active' ? 'They will be signed out and unable to sign in.' : 'They will be able to sign in again.' }}"
                    data-swal-icon="warning"
                    data-swal-confirm-button="Yes"
                    data-swal-cancel-button="Cancel">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="enabled" value="{{ $loginUser->status === 'active' ? 0 : 1 }}">
                    <button type="submit"
                        class="btn btn-sm w-100 {{ $loginUser->status === 'active' ? 'btn-light-danger' : 'btn-light-success' }}">
                        {{ $loginUser->status === 'active' ? 'Turn Off Login' : 'Turn On Login' }}
                    </button>
                </form>
            @endif
        @endif

    </div>

</div>
