<?php

use App\Models\User;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\CommissionPayout;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\CustomerMembership;
use App\Models\LoyaltyEarningRule;
use App\Models\LoyaltyProgram;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\StaffSalaryStructure;
use App\Models\StockAdjustment;
use App\Models\Unit;
use App\Models\Vendor;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Spatie\Permission\Models\Role;

// Home > Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('dashboard'));
});

// Home > Dashboard > User Management
Breadcrumbs::for('user-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('User Management', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users
Breadcrumbs::for('user-management.users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Users', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users > [User]
Breadcrumbs::for('user-management.users.show', function (BreadcrumbTrail $trail, User $user) {
    $trail->parent('user-management.users.index');
    $trail->push(ucwords($user->name), route('user-management.users.show', $user));
});

// Home > Dashboard > User Management > Roles
Breadcrumbs::for('user-management.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Roles', route('user-management.roles.index'));
});

// Home > Dashboard > User Management > Roles > [Role]
Breadcrumbs::for('user-management.roles.show', function (BreadcrumbTrail $trail, Role $role) {
    $trail->parent('user-management.roles.index');
    $trail->push(ucwords($role->name), route('user-management.roles.show', $role));
});

// Home > Dashboard > User Management > Permission
Breadcrumbs::for('user-management.permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Permissions', route('user-management.permissions.index'));
});

// Home > Dashboard > Plan Management
Breadcrumbs::for('plan-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Plan Management', route('plan-management.plans.index'));
});

// Home > Dashboard > Plan Management > Plans
Breadcrumbs::for('plan-management.plans.index', function (BreadcrumbTrail $trail) {
    $trail->parent('plan-management.index');
    $trail->push('Plans', route('plan-management.plans.index'));
});

// Home > Dashboard > Plan Management > Plans > Create
Breadcrumbs::for('plan-management.plans.create', function (BreadcrumbTrail $trail) {
    $trail->parent('plan-management.plans.index');
    $trail->push('Add Plan', route('plan-management.plans.create'));
});

// Home > Dashboard > Plan Management > Plans > [Plan]
Breadcrumbs::for('plan-management.plans.show', function (BreadcrumbTrail $trail, Plan $plan) {
    $trail->parent('plan-management.plans.index');
    $trail->push($plan->name, route('plan-management.plans.show', $plan));
});

// Home > Dashboard > Plan Management > Plans > [Plan] > Edit
Breadcrumbs::for('plan-management.plans.edit', function (BreadcrumbTrail $trail, Plan $plan) {
    $trail->parent('plan-management.plans.show', $plan);
    $trail->push('Edit', route('plan-management.plans.edit', $plan));
});

// Home > Dashboard > Plan Management > Tenant Subscriptions
Breadcrumbs::for('plan-management.subscriptions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('plan-management.index');
    $trail->push('Tenant Subscriptions', route('plan-management.subscriptions.index'));
});

// Home > Dashboard > Branches
Breadcrumbs::for('branches.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Branches', route('branches.index'));
});

// Home > Dashboard > Branches > Create
Breadcrumbs::for('branches.create', function (BreadcrumbTrail $trail) {
    $trail->parent('branches.index');
    $trail->push('Add Branch', route('branches.create'));
});

// Home > Dashboard > Branches > [Branch]
Breadcrumbs::for('branches.show', function (BreadcrumbTrail $trail, Branch $branch) {
    $trail->parent('branches.index');
    $trail->push($branch->name, route('branches.show', $branch));
});

// Home > Dashboard > Branches > [Branch] > Edit
Breadcrumbs::for('branches.edit', function (BreadcrumbTrail $trail, Branch $branch) {
    $trail->parent('branches.show', $branch);
    $trail->push('Edit', route('branches.edit', $branch));
});

// Home > Dashboard > Branches > [Branch] > Reports
Breadcrumbs::for('branches.reports.show', function (BreadcrumbTrail $trail, Branch $branch) {
    $trail->parent('branches.show', $branch);
    $trail->push('Reports', route('branches.reports.show', $branch));
});

// Home > Dashboard > Service Management
Breadcrumbs::for('service-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Service Management', route('services.index'));
});

// Home > Dashboard > Service Management > Services
Breadcrumbs::for('services.index', function (BreadcrumbTrail $trail) {
    $trail->parent('service-management.index');
    $trail->push('Services', route('services.index'));
});

// Home > Dashboard > Service Management > Services > Create
Breadcrumbs::for('services.create', function (BreadcrumbTrail $trail) {
    $trail->parent('services.index');
    $trail->push('Add Service', route('services.create'));
});

// Home > Dashboard > Service Management > Services > [Service]
Breadcrumbs::for('services.show', function (BreadcrumbTrail $trail, Service $service) {
    $trail->parent('services.index');
    $trail->push($service->name, route('services.show', $service));
});

// Home > Dashboard > Service Management > Services > [Service] > Edit
Breadcrumbs::for('services.edit', function (BreadcrumbTrail $trail, Service $service) {
    $trail->parent('services.show', $service);
    $trail->push('Edit', route('services.edit', $service));
});

// Home > Dashboard > Service Management > Categories
Breadcrumbs::for('service-categories.index', function (BreadcrumbTrail $trail) {
    $trail->parent('service-management.index');
    $trail->push('Categories', route('service-categories.index'));
});

// Home > Dashboard > Service Management > Categories > Create
Breadcrumbs::for('service-categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('service-categories.index');
    $trail->push('Add Category', route('service-categories.create'));
});

// Home > Dashboard > Service Management > Categories > [Category]
Breadcrumbs::for('service-categories.edit', function (BreadcrumbTrail $trail, ServiceCategory $serviceCategory) {
    $trail->parent('service-categories.index');
    $trail->push($serviceCategory->name, route('service-categories.edit', $serviceCategory));
});

// Home > Dashboard > Staff Management
Breadcrumbs::for('staff-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Staff Management', route('staff-management.staff.index'));
});

// Home > Dashboard > Staff Management > Staff
Breadcrumbs::for('staff-management.staff.index', function (BreadcrumbTrail $trail) {
    $trail->parent('staff-management.index');
    $trail->push('Staff', route('staff-management.staff.index'));
});

// Home > Dashboard > Staff Management > Staff > Create
Breadcrumbs::for('staff-management.staff.create', function (BreadcrumbTrail $trail) {
    $trail->parent('staff-management.staff.index');
    $trail->push('Add Staff', route('staff-management.staff.create'));
});

// Home > Dashboard > Staff Management > Staff > [Staff]
Breadcrumbs::for('staff-management.staff.show', function (BreadcrumbTrail $trail, Staff $staff) {
    $trail->parent('staff-management.staff.index');
    $trail->push($staff->full_name, route('staff-management.staff.show', $staff));
});

// Home > Dashboard > Staff Management > Staff > [Staff] > Edit
Breadcrumbs::for('staff-management.staff.edit', function (BreadcrumbTrail $trail, Staff $staff) {
    $trail->parent('staff-management.staff.show', $staff);
    $trail->push('Edit', route('staff-management.staff.edit', $staff));
});

// Home > Dashboard > Customer Management
Breadcrumbs::for('customer-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Customer Management', route('customer-management.customers.index'));
});

// Home > Dashboard > Customer Management > Customers
Breadcrumbs::for('customer-management.customers.index', function (BreadcrumbTrail $trail) {
    $trail->parent('customer-management.index');
    $trail->push('Customers', route('customer-management.customers.index'));
});

// Home > Dashboard > Customer Management > Customers > Create
Breadcrumbs::for('customer-management.customers.create', function (BreadcrumbTrail $trail) {
    $trail->parent('customer-management.customers.index');
    $trail->push('Add Customer', route('customer-management.customers.create'));
});

// Home > Dashboard > Customer Management > Customers > [Customer]
Breadcrumbs::for('customer-management.customers.show', function (BreadcrumbTrail $trail, Customer $customer) {
    $trail->parent('customer-management.customers.index');
    $trail->push($customer->full_name, route('customer-management.customers.show', $customer));
});

// Home > Dashboard > Customer Management > Customers > [Customer] > Edit
Breadcrumbs::for('customer-management.customers.edit', function (BreadcrumbTrail $trail, Customer $customer) {
    $trail->parent('customer-management.customers.show', $customer);
    $trail->push('Edit', route('customer-management.customers.edit', $customer));
});

// Home > Dashboard > Appointment Management
Breadcrumbs::for('appointment-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Appointment Management', route('appointment-management.appointments.index'));
});

// Home > Dashboard > Appointment Management > Appointments
Breadcrumbs::for('appointment-management.appointments.index', function (BreadcrumbTrail $trail) {
    $trail->parent('appointment-management.index');
    $trail->push('Appointments', route('appointment-management.appointments.index'));
});

// Home > Dashboard > Appointment Management > Calendar
Breadcrumbs::for('appointment-management.appointments.calendar', function (BreadcrumbTrail $trail) {
    $trail->parent('appointment-management.index');
    $trail->push('Calendar', route('appointment-management.appointments.calendar'));
});

// Home > Dashboard > Appointment Management > Appointments > Create
Breadcrumbs::for('appointment-management.appointments.create', function (BreadcrumbTrail $trail) {
    $trail->parent('appointment-management.appointments.index');
    $trail->push('Create Appointment', route('appointment-management.appointments.create'));
});

// Home > Dashboard > Appointment Management > Appointments > [Appointment]
Breadcrumbs::for('appointment-management.appointments.show', function (BreadcrumbTrail $trail, Appointment $appointment) {
    $trail->parent('appointment-management.appointments.index');
    $trail->push($appointment->appointment_number, route('appointment-management.appointments.show', $appointment));
});

// Home > Dashboard > Appointment Management > Appointments > [Appointment] > Edit
Breadcrumbs::for('appointment-management.appointments.edit', function (BreadcrumbTrail $trail, Appointment $appointment) {
    $trail->parent('appointment-management.appointments.show', $appointment);
    $trail->push('Edit', route('appointment-management.appointments.edit', $appointment));
});

// Home > Dashboard > Billing
Breadcrumbs::for('billing.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Billing', route('billing.invoices.index'));
});

// Home > Dashboard > Billing > Checkout
Breadcrumbs::for('billing.checkout.appointments.create', function (BreadcrumbTrail $trail, Appointment $appointment) {
    $trail->parent('billing.index');
    $trail->push('Checkout ' . $appointment->appointment_number, route('billing.checkout.appointments.create', $appointment));
});

// Home > Dashboard > Billing > Invoices
Breadcrumbs::for('billing.invoices.index', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Invoices', route('billing.invoices.index'));
});

// Home > Dashboard > Billing > Invoices > [Invoice]
Breadcrumbs::for('billing.invoices.show', function (BreadcrumbTrail $trail, Invoice $invoice) {
    $trail->parent('billing.invoices.index');
    $trail->push($invoice->invoice_number, route('billing.invoices.show', $invoice));
});

// Home > Dashboard > Billing > Invoices > [Invoice] > Receipt
// Home > Dashboard > Billing > Payments
Breadcrumbs::for('billing.payments.index', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Payments', route('billing.payments.index'));
});

// Home > Dashboard > Billing > Payment Methods
Breadcrumbs::for('billing.payment-methods.index', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Payment Methods', route('billing.payment-methods.index'));
});

// Home > Dashboard > Billing > Payment Methods > Create
Breadcrumbs::for('billing.payment-methods.create', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.payment-methods.index');
    $trail->push('Add Method', route('billing.payment-methods.create'));
});

// Home > Dashboard > Billing > Payment Methods > [Method] > Edit
Breadcrumbs::for('billing.payment-methods.edit', function (BreadcrumbTrail $trail, PaymentMethod $paymentMethod) {
    $trail->parent('billing.payment-methods.index');
    $trail->push($paymentMethod->name, route('billing.payment-methods.edit', $paymentMethod));
});

// Home > Dashboard > Inventory
Breadcrumbs::for('inventory.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Inventory', route('inventory.dashboard'));
});

Breadcrumbs::for('inventory.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Dashboard', route('inventory.dashboard'));
});

Breadcrumbs::for('inventory.products.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Products', route('inventory.products.index'));
});

Breadcrumbs::for('inventory.products.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.products.index');
    $trail->push('Add Product', route('inventory.products.create'));
});

Breadcrumbs::for('inventory.products.show', function (BreadcrumbTrail $trail, Product $product) {
    $trail->parent('inventory.products.index');
    $trail->push($product->name, route('inventory.products.show', $product));
});

Breadcrumbs::for('inventory.products.edit', function (BreadcrumbTrail $trail, Product $product) {
    $trail->parent('inventory.products.show', $product);
    $trail->push('Edit', route('inventory.products.edit', $product));
});

Breadcrumbs::for('inventory.categories.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Categories', route('inventory.categories.index'));
});

Breadcrumbs::for('inventory.categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.categories.index');
    $trail->push('Add Category', route('inventory.categories.create'));
});

Breadcrumbs::for('inventory.categories.edit', function (BreadcrumbTrail $trail, ProductCategory $category) {
    $trail->parent('inventory.categories.index');
    $trail->push($category->name, route('inventory.categories.edit', $category));
});

Breadcrumbs::for('inventory.brands.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Brands', route('inventory.brands.index'));
});

Breadcrumbs::for('inventory.brands.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.brands.index');
    $trail->push('Add Brand', route('inventory.brands.create'));
});

Breadcrumbs::for('inventory.brands.edit', function (BreadcrumbTrail $trail, ProductBrand $brand) {
    $trail->parent('inventory.brands.index');
    $trail->push($brand->name, route('inventory.brands.edit', $brand));
});

Breadcrumbs::for('inventory.units.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Units', route('inventory.units.index'));
});

Breadcrumbs::for('inventory.units.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.units.index');
    $trail->push('Add Unit', route('inventory.units.create'));
});

Breadcrumbs::for('inventory.units.edit', function (BreadcrumbTrail $trail, Unit $unit) {
    $trail->parent('inventory.units.index');
    $trail->push($unit->name, route('inventory.units.edit', $unit));
});

Breadcrumbs::for('inventory.stock.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Current Stock', route('inventory.stock.index'));
});

Breadcrumbs::for('inventory.movements.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Stock Movements', route('inventory.movements.index'));
});

Breadcrumbs::for('inventory.adjustments.index', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Stock Adjustments', route('inventory.adjustments.index'));
});

Breadcrumbs::for('inventory.adjustments.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.adjustments.index');
    $trail->push('New Adjustment', route('inventory.adjustments.create'));
});

Breadcrumbs::for('inventory.adjustments.show', function (BreadcrumbTrail $trail, StockAdjustment $adjustment) {
    $trail->parent('inventory.adjustments.index');
    $trail->push($adjustment->adjustment_number, route('inventory.adjustments.show', $adjustment));
});

// Home > Dashboard > Expense Management
Breadcrumbs::for('expense-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Expense Management', route('expense-management.expenses.index'));
});

Breadcrumbs::for('expense-management.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.index');
    $trail->push('Dashboard', route('expense-management.dashboard'));
});

Breadcrumbs::for('expense-management.expenses.index', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.index');
    $trail->push('Expenses', route('expense-management.expenses.index'));
});

Breadcrumbs::for('expense-management.expenses.create', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.expenses.index');
    $trail->push('Add Expense', route('expense-management.expenses.create'));
});

Breadcrumbs::for('expense-management.expenses.show', function (BreadcrumbTrail $trail, Expense $expense) {
    $trail->parent('expense-management.expenses.index');
    $trail->push($expense->expense_number, route('expense-management.expenses.show', $expense));
});

Breadcrumbs::for('expense-management.expenses.edit', function (BreadcrumbTrail $trail, Expense $expense) {
    $trail->parent('expense-management.expenses.show', $expense);
    $trail->push('Edit', route('expense-management.expenses.edit', $expense));
});

Breadcrumbs::for('expense-management.categories.index', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.index');
    $trail->push('Categories', route('expense-management.categories.index'));
});

Breadcrumbs::for('expense-management.categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.categories.index');
    $trail->push('Add Category', route('expense-management.categories.create'));
});

Breadcrumbs::for('expense-management.categories.edit', function (BreadcrumbTrail $trail, ExpenseCategory $category) {
    $trail->parent('expense-management.categories.index');
    $trail->push($category->name, route('expense-management.categories.edit', $category));
});

Breadcrumbs::for('expense-management.vendors.index', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.index');
    $trail->push('Vendors', route('expense-management.vendors.index'));
});

Breadcrumbs::for('expense-management.vendors.create', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.vendors.index');
    $trail->push('Add Vendor', route('expense-management.vendors.create'));
});

Breadcrumbs::for('expense-management.vendors.edit', function (BreadcrumbTrail $trail, Vendor $vendor) {
    $trail->parent('expense-management.vendors.index');
    $trail->push($vendor->name, route('expense-management.vendors.edit', $vendor));
});

Breadcrumbs::for('expense-management.reports.index', function (BreadcrumbTrail $trail) {
    $trail->parent('expense-management.index');
    $trail->push('Reports', route('expense-management.reports.index'));
});

// Home > Dashboard > Staff Commissions
Breadcrumbs::for('commission-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Staff Commissions', route('commission-management.dashboard'));
});

Breadcrumbs::for('commission-management.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.index');
    $trail->push('Overview', route('commission-management.dashboard'));
});

Breadcrumbs::for('commission-management.rules.index', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.index');
    $trail->push('Commission Rules', route('commission-management.rules.index'));
});

Breadcrumbs::for('commission-management.rules.create', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.rules.index');
    $trail->push('Create Rule', route('commission-management.rules.create'));
});

Breadcrumbs::for('commission-management.rules.edit', function (BreadcrumbTrail $trail, CommissionRule $rule) {
    $trail->parent('commission-management.rules.index');
    $trail->push('Edit Rule', route('commission-management.rules.edit', $rule));
});

Breadcrumbs::for('commission-management.ledger.index', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.index');
    $trail->push('Commission Ledger', route('commission-management.ledger.index'));
});

Breadcrumbs::for('commission-management.ledger.show', function (BreadcrumbTrail $trail, StaffCommission $commission) {
    $trail->parent('commission-management.ledger.index');
    $trail->push('Commission #' . $commission->id, route('commission-management.ledger.show', $commission));
});

Breadcrumbs::for('commission-management.payouts.index', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.index');
    $trail->push('Payouts', route('commission-management.payouts.index'));
});

Breadcrumbs::for('commission-management.payouts.create', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.payouts.index');
    $trail->push('Create Payout', route('commission-management.payouts.create'));
});

Breadcrumbs::for('commission-management.payouts.show', function (BreadcrumbTrail $trail, CommissionPayout $payout) {
    $trail->parent('commission-management.payouts.index');
    $trail->push($payout->payout_number, route('commission-management.payouts.show', $payout));
});

Breadcrumbs::for('commission-management.reports.index', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.index');
    $trail->push('Reports', route('commission-management.reports.index'));
});

Breadcrumbs::for('commission-management.settings.edit', function (BreadcrumbTrail $trail) {
    $trail->parent('commission-management.index');
    $trail->push('Settings', route('commission-management.settings.edit'));
});

// Home > Dashboard > Payroll
Breadcrumbs::for('payroll.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Payroll', route('payroll.dashboard'));
});

Breadcrumbs::for('payroll.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.index');
    $trail->push('Dashboard', route('payroll.dashboard'));
});

Breadcrumbs::for('payroll.salary-structures.index', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.index');
    $trail->push('Salary Structures', route('payroll.salary-structures.index'));
});

Breadcrumbs::for('payroll.salary-structures.create', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.salary-structures.index');
    $trail->push('Create', route('payroll.salary-structures.create'));
});

Breadcrumbs::for('payroll.salary-structures.edit', function (BreadcrumbTrail $trail, StaffSalaryStructure $salaryStructure) {
    $trail->parent('payroll.salary-structures.index');
    $trail->push($salaryStructure->staff?->full_name ?? 'Salary Structure', route('payroll.salary-structures.edit', $salaryStructure));
});

Breadcrumbs::for('payroll.periods.index', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.index');
    $trail->push('Payroll Periods', route('payroll.periods.index'));
});

Breadcrumbs::for('payroll.periods.create', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.periods.index');
    $trail->push('Create Period', route('payroll.periods.create'));
});

Breadcrumbs::for('payroll.periods.show', function (BreadcrumbTrail $trail, PayrollPeriod $period) {
    $trail->parent('payroll.periods.index');
    $trail->push($period->name, route('payroll.periods.show', $period));
});

Breadcrumbs::for('payroll.runs.index', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.index');
    $trail->push('Payroll Runs', route('payroll.runs.index'));
});

Breadcrumbs::for('payroll.runs.show', function (BreadcrumbTrail $trail, PayrollRun $payrollRun) {
    $trail->parent('payroll.runs.index');
    $trail->push($payrollRun->run_number, route('payroll.runs.show', $payrollRun));
});

Breadcrumbs::for('payroll.payslips.show', function (BreadcrumbTrail $trail, PayrollItem $payrollItem) {
    $trail->parent('payroll.runs.show', $payrollItem->run);
    $trail->push('Payslip', route('payroll.payslips.show', $payrollItem));
});

Breadcrumbs::for('payroll.reports.index', function (BreadcrumbTrail $trail) {
    $trail->parent('payroll.index');
    $trail->push('Reports', route('payroll.reports.index'));
});

// Home > Dashboard > Loyalty & Membership
Breadcrumbs::for('loyalty-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Loyalty & Membership', route('loyalty-management.dashboard'));
});

Breadcrumbs::for('loyalty-management.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Dashboard', route('loyalty-management.dashboard'));
});

Breadcrumbs::for('loyalty-management.programs.index', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Loyalty Programs', route('loyalty-management.programs.index'));
});

Breadcrumbs::for('loyalty-management.programs.create', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.programs.index');
    $trail->push('Create', route('loyalty-management.programs.create'));
});

Breadcrumbs::for('loyalty-management.programs.edit', function (BreadcrumbTrail $trail, LoyaltyProgram $program) {
    $trail->parent('loyalty-management.programs.index');
    $trail->push($program->name, route('loyalty-management.programs.edit', $program));
});

Breadcrumbs::for('loyalty-management.rules.index', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Earning Rules', route('loyalty-management.rules.index'));
});

Breadcrumbs::for('loyalty-management.rules.create', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.rules.index');
    $trail->push('Create', route('loyalty-management.rules.create'));
});

Breadcrumbs::for('loyalty-management.rules.edit', function (BreadcrumbTrail $trail, LoyaltyEarningRule $earningRule) {
    $trail->parent('loyalty-management.rules.index');
    $trail->push($earningRule->name, route('loyalty-management.rules.edit', $earningRule));
});

Breadcrumbs::for('loyalty-management.transactions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Point Transactions', route('loyalty-management.transactions.index'));
});

Breadcrumbs::for('loyalty-management.membership-plans.index', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Membership Plans', route('loyalty-management.membership-plans.index'));
});

Breadcrumbs::for('loyalty-management.membership-plans.create', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.membership-plans.index');
    $trail->push('Create', route('loyalty-management.membership-plans.create'));
});

Breadcrumbs::for('loyalty-management.membership-plans.edit', function (BreadcrumbTrail $trail, MembershipPlan $membershipPlan) {
    $trail->parent('loyalty-management.membership-plans.index');
    $trail->push($membershipPlan->name, route('loyalty-management.membership-plans.edit', $membershipPlan));
});

Breadcrumbs::for('loyalty-management.memberships.index', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Customer Memberships', route('loyalty-management.memberships.index'));
});

Breadcrumbs::for('loyalty-management.memberships.create', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.memberships.index');
    $trail->push('Purchase', route('loyalty-management.memberships.create'));
});

Breadcrumbs::for('loyalty-management.memberships.show', function (BreadcrumbTrail $trail, CustomerMembership $membership) {
    $trail->parent('loyalty-management.memberships.index');
    $trail->push($membership->membership_number, route('loyalty-management.memberships.show', $membership));
});

Breadcrumbs::for('loyalty-management.reports.index', function (BreadcrumbTrail $trail) {
    $trail->parent('loyalty-management.index');
    $trail->push('Reports', route('loyalty-management.reports.index'));
});

// Home > Dashboard > Promotions & Discounts
Breadcrumbs::for('promotions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Promotions & Discounts', route('promotions.dashboard'));
});

Breadcrumbs::for('promotions.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.index');
    $trail->push('Dashboard', route('promotions.dashboard'));
});

Breadcrumbs::for('promotions.promotions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.index');
    $trail->push('Promotions', route('promotions.promotions.index'));
});

Breadcrumbs::for('promotions.promotions.create', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.promotions.index');
    $trail->push('Create', route('promotions.promotions.create'));
});

Breadcrumbs::for('promotions.promotions.show', function (BreadcrumbTrail $trail, Promotion $promotion) {
    $trail->parent('promotions.promotions.index');
    $trail->push($promotion->name, route('promotions.promotions.show', $promotion));
});

Breadcrumbs::for('promotions.promotions.edit', function (BreadcrumbTrail $trail, Promotion $promotion) {
    $trail->parent('promotions.promotions.show', $promotion);
    $trail->push('Edit', route('promotions.promotions.edit', $promotion));
});

Breadcrumbs::for('promotions.coupons.index', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.index');
    $trail->push('Coupons', route('promotions.coupons.index'));
});

Breadcrumbs::for('promotions.coupons.create', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.coupons.index');
    $trail->push('Create', route('promotions.coupons.create'));
});

Breadcrumbs::for('promotions.coupons.edit', function (BreadcrumbTrail $trail, PromotionCoupon $coupon) {
    $trail->parent('promotions.coupons.index');
    $trail->push($coupon->code, route('promotions.coupons.edit', $coupon));
});

Breadcrumbs::for('promotions.usages.index', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.index');
    $trail->push('Usage History', route('promotions.usages.index'));
});

Breadcrumbs::for('promotions.reports.index', function (BreadcrumbTrail $trail) {
    $trail->parent('promotions.index');
    $trail->push('Reports', route('promotions.reports.index'));
});
