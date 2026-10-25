{{-- Two-factor authentication: off → setup (QR + code) → on (recovery codes, turn off). --}}
@php
    $twoFactorUser = auth()->user();
    $twoFactorRequired = app(\App\Services\Auth\TwoFactorService::class)->requiredFor($twoFactorUser);
@endphp

<div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
    <i class="ki-duotone ki-shield-tick fs-2tx text-primary me-4">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>

    <div class="flex-grow-1">
        <div class="d-flex flex-stack flex-wrap gap-3 mb-3">
            <div class="fw-semibold">
                <h4 class="text-gray-900 fw-bold mb-1">
                    Two-Factor Authentication
                    @if ($twoFactorUser->hasTwoFactorEnabled())
                        <span class="badge badge-light-success ms-2">On</span>
                    @else
                        <span class="badge badge-light-secondary ms-2">Off</span>
                    @endif
                </h4>
                <div class="fs-6 text-gray-700">
                    When signing in you'll also enter a 6-digit code from an authenticator app such as Google
                    Authenticator or Microsoft Authenticator.
                    @if ($twoFactorRequired)
                        <span class="fw-bold">Your salon requires this.</span>
                    @endif
                </div>
            </div>
        </div>

        @if (session('two_factor_recovery_codes'))
            <div class="alert alert-warning mb-5">
                <div class="fw-bold mb-2">Save these recovery codes somewhere safe.</div>
                <div class="fs-7 mb-3">Each one signs you in once if you lose your phone. They won't be shown again.</div>
                <div class="row g-2 font-monospace">
                    @foreach (session('two_factor_recovery_codes') as $recoveryCode)
                        <div class="col-6 col-md-3"><code>{{ $recoveryCode }}</code></div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($twoFactorUser->hasTwoFactorEnabled())
            <div class="text-muted fs-7 mb-4">
                On since {{ $twoFactorUser->two_factor_confirmed_at->format('d M Y') }} ·
                {{ count($twoFactorUser->two_factor_recovery_codes ?? []) }} recovery codes left
            </div>
            <div class="d-flex flex-column flex-md-row gap-3">
                <form method="POST" action="{{ route('profile.two-factor.recovery-codes') }}" class="d-flex gap-2">
                    @csrf
                    <input type="password" name="current_password" class="form-control form-control-sm"
                        placeholder="Current password" required autocomplete="current-password">
                    <button type="submit" class="btn btn-sm btn-light-primary text-nowrap">New recovery codes</button>
                </form>
                @unless ($twoFactorRequired)
                    <form method="POST" action="{{ route('profile.two-factor.disable') }}" class="d-flex gap-2">
                        @csrf
                        @method('DELETE')
                        <input type="password" name="current_password" class="form-control form-control-sm"
                            placeholder="Current password" required autocomplete="current-password">
                        <button type="submit" class="btn btn-sm btn-light-danger text-nowrap">Turn off</button>
                    </form>
                @endunless
            </div>
        @elseif ($twoFactorUser->two_factor_secret)
            <div class="d-flex flex-column flex-md-row gap-6 align-items-md-center">
                <div class="bg-white p-3 rounded border flex-shrink-0" style="width: 200px">
                    {!! app(\App\Services\Auth\TwoFactorService::class)->qrCodeSvg($twoFactorUser) !!}
                </div>
                <div class="flex-grow-1">
                    <div class="fs-7 text-gray-700 mb-2">
                        1. Scan the QR code with your authenticator app, or enter this key:
                    </div>
                    <div class="font-monospace fw-bold fs-6 mb-4 text-break">
                        {{ trim(chunk_split($twoFactorUser->two_factor_secret, 4, ' ')) }}
                    </div>
                    <div class="fs-7 text-gray-700 mb-2">2. Enter the 6-digit code the app shows:</div>
                    <form method="POST" action="{{ route('profile.two-factor.confirm') }}" class="d-flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="code" class="form-control form-control-sm w-150px" inputmode="numeric"
                            autocomplete="one-time-code" maxlength="6" placeholder="000000" required>
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">Confirm & turn on</button>
                    </form>
                    <form method="POST" action="{{ route('profile.two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-gray-600 p-0">Cancel setup</button>
                    </form>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('profile.two-factor.enable') }}" class="d-flex flex-column flex-md-row gap-2">
                @csrf
                <input type="password" name="current_password" class="form-control form-control-sm w-md-250px"
                    placeholder="Current password" required autocomplete="current-password">
                <button type="submit" class="btn btn-sm btn-primary text-nowrap">Turn on</button>
            </form>
        @endif
    </div>
</div>
