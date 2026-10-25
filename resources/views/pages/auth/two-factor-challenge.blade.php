<x-auth-layout>

    <form class="form w-100" method="POST" action="{{ route('two-factor.challenge.store') }}">
        @csrf

        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">
                Two-step verification
            </h1>

            <div class="text-gray-500 fw-semibold fs-6">
                Enter the 6-digit code from your authenticator app.
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger p-5 mb-8">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="fv-row mb-8">
            <input class="form-control bg-transparent text-center fs-2 fw-bold" type="text" name="code"
                inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" autofocus />
        </div>

        <div class="d-grid mb-8">
            <button type="submit" class="btn btn-primary">Verify</button>
        </div>

        <div class="separator separator-content my-8">
            <span class="w-200px text-gray-500 fw-semibold fs-7">Lost your phone?</span>
        </div>

        <div class="fv-row mb-4">
            <input class="form-control bg-transparent" type="text" name="recovery_code" autocomplete="off"
                placeholder="Recovery code (xxxxx-xxxxx)" />
        </div>
        <div class="text-muted fs-7 mb-8">
            Leave the 6-digit code empty and enter one of the recovery codes you saved when you turned this on.
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="btn btn-link text-gray-600">Sign out</button>
    </form>

</x-auth-layout>
