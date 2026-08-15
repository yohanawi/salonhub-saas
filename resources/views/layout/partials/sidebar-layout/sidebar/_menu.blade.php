<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true"
        data-kt-scroll-activate="true" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
        data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">

        <div class="menu menu-column menu-rounded menu-sub-indention px-3 fw-semibold fs-6" id="#kt_app_sidebar_menu"
            data-kt-menu="true" data-kt-menu-expand="false">

            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}">
                    <span class="menu-icon">{!! getIcon('element-11', 'fs-2') !!}</span>
                    <span class="menu-title">Dashboards</span>
                </a>
            </div>

            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Apps</span>
                </div>
            </div>

            @if (auth()->user()?->hasRole('Super Admin'))
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('plan-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('credit-cart', 'fs-2') !!}</span>
                        <span class="menu-title">Plan Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('plan-management.plans.index') ? 'active' : '' }}"
                                href="{{ route('plan-management.plans.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Plans</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('plan-management.plans.create') ? 'active' : '' }}"
                                href="{{ route('plan-management.plans.create') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Add Plan</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('plan-management.subscriptions.*') ? 'active' : '' }}"
                                href="{{ route('plan-management.subscriptions.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Subscriptions</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div data-kt-menu-trigger="click"
                class="menu-item menu-accordion {{ request()->routeIs('branches.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">{!! getIcon('abstract-41', 'fs-2') !!}</span>
                    <span class="menu-title">Branch Management</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('branches.index') || request()->routeIs('branches.show') ? 'active' : '' }}"
                            href="{{ route('branches.index') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Branches</span>
                        </a>
                    </div>
                    @can('create', \App\Models\Branch::class)
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('branches.create') ? 'active' : '' }}"
                                href="{{ route('branches.create') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Add Branch</span>
                            </a>
                        </div>
                    @endcan
                </div>
            </div>

            @can('viewAny', \App\Models\Service::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('services.*') || request()->routeIs('service-categories.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('abstract-26', 'fs-2') !!}</span>
                        <span class="menu-title">Service Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('services.*') ? 'active' : '' }}"
                                href="{{ route('services.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Services</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('service-categories.*') ? 'active' : '' }}"
                                href="{{ route('service-categories.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Categories</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\Staff::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('staff-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('profile-user', 'fs-2') !!}</span>
                        <span class="menu-title">Staff Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('staff-management.staff.index') || request()->routeIs('staff-management.staff.show') ? 'active' : '' }}"
                                href="{{ route('staff-management.staff.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Staff</span>
                            </a>
                        </div>
                        @can('create', \App\Models\Staff::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('staff-management.staff.create') ? 'active' : '' }}"
                                    href="{{ route('staff-management.staff.create') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Add Staff</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\Customer::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('customer-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('people', 'fs-2') !!}</span>
                        <span class="menu-title">Customer Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('customer-management.customers.index') || request()->routeIs('customer-management.customers.show') ? 'active' : '' }}"
                                href="{{ route('customer-management.customers.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Customers</span>
                            </a>
                        </div>
                        @can('create', \App\Models\Customer::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('customer-management.customers.create') ? 'active' : '' }}"
                                    href="{{ route('customer-management.customers.create') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Add Customer</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            <div data-kt-menu-trigger="click"
                class="menu-item menu-accordion {{ request()->routeIs('user-management.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">{!! getIcon('abstract-28', 'fs-2') !!}</span>
                    <span class="menu-title">User Management</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('user-management.users.*') ? 'active' : '' }}"
                            href="{{ route('user-management.users.index') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Users</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('user-management.roles.*') ? 'active' : '' }}"
                            href="{{ route('user-management.roles.index') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Roles</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('user-management.permissions.*') ? 'active' : '' }}"
                            href="{{ route('user-management.permissions.index') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Permissions</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Help</span>
                </div>
            </div>

        </div>
    </div>
</div>
