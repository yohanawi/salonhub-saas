<x-auth-layout>

    <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form" data-kt-redirect-url="{{ route('verification.notice') }}"
        action="{{ route('register') }}">
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">
                Sign Up
            </h1>
            <div class="text-gray-500 fw-semibold fs-6">
                Where Beauty Meets Better Management
            </div>
        </div>

        <div class="d-flex justify-content-center mb-9">
            <a href="{{ url('/auth/redirect/google') }}?redirect_uri={{ url()->current() }}"
                class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-50">
                <img alt="Logo" src="{{ image('svg/brand-logos/google-icon.svg') }}" class="h-15px me-3" />
                Sign in with Google
            </a>
        </div>

        <div class="separator separator-content my-14">
            <span class="w-125px text-gray-500 fw-semibold fs-7">Or with email</span>
        </div>

        <div class="fv-row mb-8">
            <input type="text" placeholder="Business Name" name="business_name" autocomplete="off"
                class="form-control bg-transparent" />
        </div>

        <div class="row g-3 mb-8">
            <div class="fv-row col-12 col-sm-6">
                <input type="text" placeholder="First Name" name="first_name" autocomplete="off"
                    class="form-control bg-transparent" />
            </div>
            <div class="fv-row col-12 col-sm-6">
                <input type="text" placeholder="Last Name" name="last_name" autocomplete="off"
                    class="form-control bg-transparent" />
            </div>
        </div>

        <div class="fv-row mb-8">
            <input type="text" placeholder="Email" name="email" autocomplete="off"
                class="form-control bg-transparent" />
        </div>

        <div class="fv-row mb-8" data-kt-password-meter="true">
            <div class="mb-1">
                <div class="position-relative mb-3">
                    <input class="form-control bg-transparent" type="password" placeholder="Password" name="password"
                        autocomplete="off" />

                    <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                        data-kt-password-meter-control="visibility">
                        <i class="bi bi-eye-slash fs-2"></i>
                        <i class="bi bi-eye fs-2 d-none"></i>
                    </span>
                </div>

                <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                </div>
            </div>

            <div class="text-muted">
                Use 8 or more characters with a mix of letters, numbers & symbols.
            </div>
        </div>

        <div class="fv-row mb-8">
            <input placeholder="Repeat Password" name="password_confirmation" type="password" autocomplete="off"
                class="form-control bg-transparent" />
        </div>

        <div class="fv-row mb-10">
            <div class="form-check form-check-custom form-check-solid form-check-inline">
                <input class="form-check-input" type="checkbox" name="toc" value="1" />

                <label class="form-check-label fw-semibold text-gray-700 fs-6">
                    I agree to the
                    <a href="{{ route('terms') }}" class="ms-1 link-primary" target="_blank">Terms and conditions</a>.
                </label>
            </div>
        </div>

        <div class="d-grid mb-10">
            <button type="submit" id="kt_sign_up_submit" class="btn btn-primary">
                @include('partials/general/_button-indicator', ['label' => 'Sign Up'])
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            Already have an Account?

            <a href="/login" class="link-primary fw-semibold">
                Sign in
            </a>
        </div>
    </form>

</x-auth-layout>
