<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
    data-kt-menu="true">
    <div class="menu-item px-3">
        <div class="menu-content d-flex align-items-center px-3">
            <div class="symbol symbol-50px me-5">
                @if (Auth::user()->profile_photo_url)
                    <img alt="Logo" src="{{ Auth::user()->profile_photo_url }}" />
                @else
                    <div
                        class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', Auth::user()->name) }}">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="d-flex flex-column">
                <div class="fw-bold d-flex align-items-center fs-5">{{ Auth::user()->name }}
                    <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">Pro</span>
                </div>
                <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ Auth::user()->email }}</a>
            </div>
        </div>
    </div>
    <div class="separator my-2"></div>
    <div class="menu-item px-5">
        <a href="{{ route('profile.show') }}" class="menu-link px-5">My Profile</a>
    </div>
    @if ($accountStaff = Auth::user()->staffProfile)
        @php($todayAppointmentsCount = app(\App\Services\Appointment\StaffAgendaService::class)->todayCount($accountStaff))
        <div class="menu-item px-5">
            <a href="{{ route('profile.appointments') }}" class="menu-link px-5">
                <span class="menu-text">My Appointments</span>
                @if ($todayAppointmentsCount > 0)
                    <span class="menu-badge" data-bs-toggle="tooltip" title="Appointments today">
                        <span class="badge badge-light-danger badge-circle fw-bold fs-7">{{ $todayAppointmentsCount }}</span>
                    </span>
                @endif
            </a>
        </div>
        <div class="menu-item px-5">
            <a href="{{ route('profile.attendance') }}" class="menu-link px-5">Clock In / Out</a>
        </div>
        @if (Auth::user()->can('commissions.view_own') || Auth::user()->can('commissions.view'))
            <div class="menu-item px-5">
                <a href="{{ route('profile.commissions') }}" class="menu-link px-5">My Commissions</a>
            </div>
        @endif
    @endif
    <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
        data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
        <a href="#" class="menu-link px-5">
            <span class="menu-title">My Subscription</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="menu-sub menu-sub-dropdown w-175px py-4">
            <div class="menu-item px-3">
                <a href="{{ route('profile.billing') }}" class="menu-link px-5">Billing</a>
            </div>
            <div class="separator my-2"></div>
            <div class="menu-item px-3">
                <div class="menu-content px-3">
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input w-30px h-20px" type="checkbox" value="1" checked="checked"
                            name="notifications" />
                        <span class="form-check-label text-muted fs-7">Notifications</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    @if (Auth::user()->tenant_id || Auth::user()->staffProfile)
        <div class="menu-item px-5">
            <a href="{{ route('profile.statements') }}" class="menu-link px-5">My Statements</a>
        </div>
    @endif
    <div class="separator my-2"></div>
    <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
        data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
        <a href="#" class="menu-link px-5">
            <span class="menu-title position-relative">Mode
                <span class="ms-5 position-absolute translate-middle-y top-50 end-0">{!! getIcon('night-day', 'theme-light-show fs-2') !!}
                    {!! getIcon('moon', 'theme-dark-show fs-2') !!}</span></span>
        </a>
        @include('partials/theme-mode/__menu')
    </div>
    <div class="menu-item px-5 my-1">
        <a href="{{ route('profile.settings') }}" class="menu-link px-5">Account Settings</a>
    </div>
    <div class="menu-item px-5">
        <a class="button-ajax menu-link px-5" href="#" data-action="{{ route('logout') }}" data-method="post"
            data-csrf="{{ csrf_token() }}" data-reload="true">
            Sign Out
        </a>
    </div>
</div>
