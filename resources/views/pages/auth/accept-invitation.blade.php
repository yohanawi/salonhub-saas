<x-auth-layout>

    @if (! $valid)
        <div class="text-center">
            <h1 class="text-gray-900 fw-bolder mb-3">
                Invitation expired
            </h1>

            <div class="text-gray-500 fw-semibold fs-6 mb-10">
                This invitation link has expired or was already used. Ask your salon to send a new invite.
            </div>

            <a href="{{ route('login') }}" class="btn btn-primary">Go to sign in</a>
        </div>
    @else
        <form class="form w-100" method="POST" action="{{ request()->fullUrl() }}">
            @csrf

            <div class="text-center mb-10">
                <h1 class="text-gray-900 fw-bolder mb-3">
                    Join {{ $invitee->tenant?->name }}
                </h1>

                <div class="text-gray-500 fw-semibold fs-6">
                    Choose a password for <span class="fw-bold text-gray-800">{{ $invitee->email }}</span>.
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger p-5 mb-8">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="fv-row mb-8">
                <input class="form-control bg-transparent" type="password" placeholder="Password" name="password"
                    autocomplete="new-password" required />
                <div class="text-muted fs-7 mt-2">
                    Use 8 or more characters.
                </div>
            </div>

            <div class="fv-row mb-8">
                <input class="form-control bg-transparent" type="password" placeholder="Repeat Password"
                    name="password_confirmation" autocomplete="new-password" required />
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    Activate account
                </button>
            </div>
        </form>
    @endif

</x-auth-layout>
