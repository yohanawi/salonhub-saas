<x-auth-layout>
    <form class="form w-100" method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">Confirm Password</h1>
            <div class="text-gray-500 fw-semibold fs-6">
                This is a secure area of the application. Please confirm your password before continuing.
            </div>
        </div>

        @error('password')
            <div class="alert alert-danger p-5 mb-8">
                {{ $message }}
            </div>
        @enderror

        <div class="fv-row mb-8 fv-plugins-icon-container">
            <input placeholder="Password" type="password" name="password" autocomplete="current-password"
                class="form-control bg-transparent">
        </div>

        <div class="d-flex flex-wrap justify-content-center pb-lg-0">
            <button type="submit" class="btn btn-primary me-4">
                @include('partials/general/_button-indicator', ['label' => 'Confirm'])
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</x-auth-layout>
