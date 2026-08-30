<x-auth-layout>
    <div class="w-100">
        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">
                Verify your email
            </h1>

            <div class="text-gray-500 fw-semibold fs-6">
                We sent a verification link to
                <span class="text-gray-800 fw-bold">{{ auth()->user()->email }}</span>.
                Open that email and click the verification link to activate your account.
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success d-flex align-items-center p-5 mt-8 mb-0">
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">
                            A new verification link has been sent to your email address.
                        </span>
                    </div>
                </div>
            @endif

            @if (session('status') === 'verification-link-failed')
                <div class="alert alert-danger d-flex align-items-center p-5 mt-8 mb-0">
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">
                            We could not send the verification email right now. Please check the SMTP settings and try
                            again.
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <div class="d-grid gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-lg btn-primary fw-bolder w-100">
                    Resend verification email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-lg btn-light-primary fw-bolder w-100">
                    Log out
                </button>
            </form>
        </div>

        <div class="text-center text-muted fw-semibold fs-7 mt-8">
            After verification, you will be redirected to the dashboard.
        </div>
    </div>
</x-auth-layout>
