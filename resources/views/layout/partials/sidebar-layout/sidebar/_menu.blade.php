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
                    <span class="menu-heading fw-bold text-uppercase fs-7">ADMINISTRATION</span>
                </div>
            </div>

            @if (auth()->user()?->hasRole('Super Admin'))
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('plan-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('credit-cart', 'fs-2') !!}</span>
                        <span class="menu-title">Plans & Subscriptions</span>
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
                                <span class="menu-title">Add New Plan</span>
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
                    <span class="menu-heading fw-bold text-uppercase fs-7">SALON</span>
                </div>
            </div>

            <div data-kt-menu-trigger="click"
                class="menu-item menu-accordion {{ request()->routeIs('branches.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">{!! getIcon('abstract-41', 'fs-2') !!}</span>
                    <span class="menu-title">Branches</span>
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
                        <span class="menu-title">Services</span>
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
                        <span class="menu-title">Teams</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('staff-management.staff.index') || request()->routeIs('staff-management.staff.show') ? 'active' : '' }}"
                                href="{{ route('staff-management.staff.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Staff Members</span>
                            </a>
                        </div>
                        @can('create', \App\Models\Staff::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('staff-management.staff.create') ? 'active' : '' }}"
                                    href="{{ route('staff-management.staff.create') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Add Staff Member</span>
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
                        <span class="menu-title">Customers</span>
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

            @can('viewAny', \App\Models\Appointment::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('appointment-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('calendar-8', 'fs-2') !!}</span>
                        <span class="menu-title">Appointments</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('appointment-management.appointments.index') || request()->routeIs('appointment-management.appointments.show') ? 'active' : '' }}"
                                href="{{ route('appointment-management.appointments.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Appointments</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('appointment-management.appointments.calendar') ? 'active' : '' }}"
                                href="{{ route('appointment-management.appointments.calendar') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Calendar</span>
                            </a>
                        </div>
                        @can('create', \App\Models\Appointment::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('appointment-management.appointments.create') ? 'active' : '' }}"
                                    href="{{ route('appointment-management.appointments.create') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Create Appointment</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">SALES & GROWTH</span>
                </div>
            </div>

            @can('viewAny', \App\Models\Invoice::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('billing.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('wallet', 'fs-2') !!}</span>
                        <span class="menu-title">Billing / POS</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('billing.invoices.*') ? 'active' : '' }}"
                                href="{{ route('billing.invoices.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Invoices</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\Payment::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('billing.payments.*') ? 'active' : '' }}"
                                    href="{{ route('billing.payments.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Payments</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\PaymentMethod::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('billing.payment-methods.*') ? 'active' : '' }}"
                                    href="{{ route('billing.payment-methods.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Payment Methods</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\Product::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('inventory.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('parcel', 'fs-2') !!}</span>
                        <span class="menu-title">Inventory</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('inventory.dashboard') ? 'active' : '' }}"
                                href="{{ route('inventory.dashboard') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('inventory.products.*') ? 'active' : '' }}"
                                href="{{ route('inventory.products.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Products</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\ProductCategory::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('inventory.categories.*') ? 'active' : '' }}"
                                    href="{{ route('inventory.categories.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Categories</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\ProductBrand::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('inventory.brands.*') ? 'active' : '' }}"
                                    href="{{ route('inventory.brands.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Brands</span>
                                </a>
                            </div>
                        @endcan
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('inventory.stock.*') ? 'active' : '' }}"
                                href="{{ route('inventory.stock.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Current Stock</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\StockMovement::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('inventory.movements.*') ? 'active' : '' }}"
                                    href="{{ route('inventory.movements.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Stock Movements</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\StockAdjustment::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}"
                                    href="{{ route('inventory.adjustments.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Stock Adjustments</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\LoyaltyProgram::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('loyalty-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('gift', 'fs-2') !!}</span>
                        <span class="menu-title">Loyalty & Membership</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('loyalty-management.dashboard') ? 'active' : '' }}"
                                href="{{ route('loyalty-management.dashboard') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('loyalty-management.programs.*') ? 'active' : '' }}"
                                href="{{ route('loyalty-management.programs.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Loyalty Program</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('loyalty-management.rules.*') ? 'active' : '' }}"
                                href="{{ route('loyalty-management.rules.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Earning Rules</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('loyalty-management.transactions.*') ? 'active' : '' }}"
                                href="{{ route('loyalty-management.transactions.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Point Transactions</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\MembershipPlan::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('loyalty-management.membership-plans.*') ? 'active' : '' }}"
                                    href="{{ route('loyalty-management.membership-plans.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Membership Plans</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\CustomerMembership::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('loyalty-management.memberships.*') ? 'active' : '' }}"
                                    href="{{ route('loyalty-management.memberships.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Customer Memberships</span>
                                </a>
                            </div>
                        @endcan
                        @can('loyalty_reports.view')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('loyalty-management.reports.*') ? 'active' : '' }}"
                                    href="{{ route('loyalty-management.reports.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Reports</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\Promotion::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('promotions.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('discount', 'fs-2') !!}</span>
                        <span class="menu-title">Promotions & Discounts</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('promotions.dashboard') ? 'active' : '' }}"
                                href="{{ route('promotions.dashboard') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('promotions.promotions.*') ? 'active' : '' }}"
                                href="{{ route('promotions.promotions.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Promotions</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\PromotionCoupon::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('promotions.coupons.*') ? 'active' : '' }}"
                                    href="{{ route('promotions.coupons.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Coupons</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\PromotionUsage::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('promotions.usages.*') ? 'active' : '' }}"
                                    href="{{ route('promotions.usages.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Usage History</span>
                                </a>
                            </div>
                        @endcan
                        @can('promotion_reports.view')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('promotions.reports.*') ? 'active' : '' }}"
                                    href="{{ route('promotions.reports.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Reports</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">FINANCE & STAFF PAY</span>
                </div>
            </div>

            @can('viewAny', \App\Models\PayrollRun::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('payroll.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('dollar', 'fs-2') !!}</span>
                        <span class="menu-title">Payroll</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('payroll.dashboard') ? 'active' : '' }}"
                                href="{{ route('payroll.dashboard') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"
                                href="{{ route('payroll.runs.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Payroll Runs</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('payroll.periods.*') ? 'active' : '' }}"
                                href="{{ route('payroll.periods.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Payroll Periods</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\StaffSalaryStructure::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('payroll.salary-structures.*') ? 'active' : '' }}"
                                    href="{{ route('payroll.salary-structures.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Salary Structures</span>
                                </a>
                            </div>
                        @endcan
                        @can('payroll.report.view')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('payroll.reports.*') ? 'active' : '' }}"
                                    href="{{ route('payroll.reports.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Reports</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\StaffCommission::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('commission-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('chart-line-up', 'fs-2') !!}</span>
                        <span class="menu-title">Staff Commissions</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('commission-management.dashboard') ? 'active' : '' }}"
                                href="{{ route('commission-management.dashboard') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Overview</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\CommissionRule::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('commission-management.rules.*') ? 'active' : '' }}"
                                    href="{{ route('commission-management.rules.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Rules</span>
                                </a>
                            </div>
                        @endcan
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('commission-management.ledger.*') ? 'active' : '' }}"
                                href="{{ route('commission-management.ledger.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Ledger</span>
                            </a>
                        </div>
                        @can('viewAny', \App\Models\CommissionPayout::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('commission-management.payouts.*') ? 'active' : '' }}"
                                    href="{{ route('commission-management.payouts.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Payouts</span>
                                </a>
                            </div>
                        @endcan
                        @can('commission_reports.view')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('commission-management.reports.*') ? 'active' : '' }}"
                                    href="{{ route('commission-management.reports.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Reports</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\CommissionSetting::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('commission-management.settings.*') ? 'active' : '' }}"
                                    href="{{ route('commission-management.settings.edit') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Settings</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('viewAny', \App\Models\Expense::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('expense-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('bill', 'fs-2') !!}</span>
                        <span class="menu-title">Expense Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('expense-management.dashboard') ? 'active' : '' }}"
                                href="{{ route('expense-management.dashboard') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('expense-management.expenses.*') ? 'active' : '' }}"
                                href="{{ route('expense-management.expenses.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Expenses</span>
                            </a>
                        </div>
                        @can('create', \App\Models\Expense::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('expense-management.expenses.create') ? 'active' : '' }}"
                                    href="{{ route('expense-management.expenses.create') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Add Expense</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\ExpenseCategory::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('expense-management.categories.*') ? 'active' : '' }}"
                                    href="{{ route('expense-management.categories.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Categories</span>
                                </a>
                            </div>
                        @endcan
                        @can('viewAny', \App\Models\Vendor::class)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('expense-management.vendors.*') ? 'active' : '' }}"
                                    href="{{ route('expense-management.vendors.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Vendors</span>
                                </a>
                            </div>
                        @endcan
                        @can('expense_reports.view')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('expense-management.reports.*') ? 'active' : '' }}"
                                    href="{{ route('expense-management.reports.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Reports</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @endcan

            @if (auth()->user()?->can('viewAny', \App\Models\Setting::class) || auth()->user()?->can('viewAny', \App\Models\AuditLog::class))
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">CONTROL CENTER</span>
                    </div>
                </div>
            @endif

            @can('viewAny', \App\Models\AuditLog::class)
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}"
                        href="{{ route('audit-logs.index') }}">
                        <span class="menu-icon">{!! getIcon('shield-tick', 'fs-2') !!}</span>
                        <span class="menu-title">Audit Logs</span>
                    </a>
                </div>
            @endcan

            @can('viewAny', \App\Models\Setting::class)
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('settings.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('setting-2', 'fs-2') !!}</span>
                        <span class="menu-title">Settings Center</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('settings.index') ? 'active' : '' }}"
                                href="{{ route('settings.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Overview</span>
                            </a>
                        </div>
                        @foreach (['general' => 'General', 'appointments' => 'Appointments', 'sales' => 'Sales', 'team' => 'Team', 'inventory' => 'Inventory', 'customers' => 'Customers', 'communications' => 'Communications', 'security' => 'Security', 'integrations' => 'Integrations'] as $settingsSection => $settingsLabel)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('settings.edit') && request()->route('section') === $settingsSection ? 'active' : '' }}"
                                    href="{{ route('settings.edit', $settingsSection) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">{{ $settingsLabel }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endcan

        </div>
    </div>
</div>
