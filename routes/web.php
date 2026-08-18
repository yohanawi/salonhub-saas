<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\PlanManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Appointment\AppointmentAvailabilityController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\AppointmentStatusController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\PaymentMethodController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Branch\BranchArchiveController;
use App\Http\Controllers\Branch\BranchReportController;
use App\Http\Controllers\Branch\BranchSpecialHourController;
use App\Http\Controllers\Branch\BranchStatusController;
use App\Http\Controllers\Branch\SwitchBranchController;
use App\Http\Controllers\Branch\UpdateBranchHoursController;
use App\Http\Controllers\Commission\CommissionDashboardController;
use App\Http\Controllers\Commission\CommissionPayoutController;
use App\Http\Controllers\Commission\CommissionReportController;
use App\Http\Controllers\Commission\CommissionRuleController;
use App\Http\Controllers\Commission\CommissionSettingController;
use App\Http\Controllers\Commission\StaffCommissionController;
use App\Http\Controllers\CustomerManagement\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Expense\ExpenseApprovalController;
use App\Http\Controllers\Expense\ExpenseAttachmentController;
use App\Http\Controllers\Expense\ExpenseCategoryController as ExpenseCategoryController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Expense\ExpenseDashboardController;
use App\Http\Controllers\Expense\ExpensePaymentController;
use App\Http\Controllers\Expense\ExpenseReportController;
use App\Http\Controllers\Expense\VendorController;
use App\Http\Controllers\Inventory\CurrentStockController;
use App\Http\Controllers\Inventory\InventoryDashboardController;
use App\Http\Controllers\Inventory\ProductBrandController;
use App\Http\Controllers\Inventory\ProductCategoryController;
use App\Http\Controllers\Inventory\ProductController as InventoryProductController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\UnitController;
use App\Http\Controllers\Loyalty\CustomerMembershipController;
use App\Http\Controllers\Loyalty\LoyaltyDashboardController;
use App\Http\Controllers\Loyalty\LoyaltyEarningRuleController;
use App\Http\Controllers\Loyalty\LoyaltyPointTransactionController;
use App\Http\Controllers\Loyalty\LoyaltyProgramController;
use App\Http\Controllers\Loyalty\LoyaltyReportController;
use App\Http\Controllers\Loyalty\MembershipPlanController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Payroll\PayrollDashboardController;
use App\Http\Controllers\Payroll\PayrollPaymentController;
use App\Http\Controllers\Payroll\PayrollPeriodController;
use App\Http\Controllers\Payroll\PayrollReportController;
use App\Http\Controllers\Payroll\PayrollRunController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\Payroll\SalaryStructureController;
use App\Http\Controllers\Promotions\PromotionController;
use App\Http\Controllers\Promotions\PromotionCouponController;
use App\Http\Controllers\Promotions\PromotionDashboardController;
use App\Http\Controllers\Promotions\PromotionReportController;
use App\Http\Controllers\Promotions\PromotionUsageController;
use App\Http\Controllers\Service\ServiceCategoryController;
use App\Http\Controllers\Service\ServiceController;
use App\Http\Controllers\Service\ServiceStatusController;
use App\Http\Controllers\Staff\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    Route::name('user-management.')->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });

    Route::name('plan-management.')->group(function () {
        Route::get('/plan-management/subscriptions', [PlanManagementController::class, 'subscriptions'])->name('subscriptions.index');
        Route::patch('/plan-management/subscriptions/{tenant}', [PlanManagementController::class, 'updateSubscription'])->name('subscriptions.update');
        Route::patch('/plan-management/plans/{plan}/status', [PlanManagementController::class, 'updateStatus'])->name('plans.status.update');
        Route::resource('/plan-management/plans', PlanManagementController::class);
    });

    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::get('/create', [BranchController::class, 'create'])->name('create');
        Route::post('/', [BranchController::class, 'store'])->name('store');
        Route::delete('/current', [SwitchBranchController::class, 'clear'])->name('switch.clear');
        Route::get('/{branch}', [BranchController::class, 'show'])->name('show');
        Route::get('/{branch}/reports', [BranchReportController::class, 'show'])->name('reports.show');
        Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
        Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
        Route::delete('/{branch}', [BranchArchiveController::class, 'archive'])->name('archive');
        Route::post('/{branch}/restore', [BranchArchiveController::class, 'restore'])->name('restore');
        Route::patch('/{branch}/status', [BranchStatusController::class, 'update'])->name('status.update');
        Route::put('/{branch}/hours', UpdateBranchHoursController::class)->name('hours.update');
        Route::post('/{branch}/special-hours', [BranchSpecialHourController::class, 'store'])->name('special-hours.store');
        Route::delete('/{branch}/special-hours/{specialHour}', [BranchSpecialHourController::class, 'destroy'])->name('special-hours.destroy');
        Route::post('/{branch}/switch', SwitchBranchController::class)->name('switch');
    });

    Route::resource('/service-categories', ServiceCategoryController::class)
        ->except(['show']);

    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/create', [ServiceController::class, 'create'])->name('create');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
        Route::get('/{service}/edit', [ServiceController::class, 'edit'])->name('edit');
        Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
        Route::patch('/{service}/status', [ServiceStatusController::class, 'update'])->name('status.update');
    });

    Route::prefix('staff-management')->name('staff-management.')->group(function () {
        Route::resource('/staff', StaffController::class)
            ->parameters(['staff' => 'staff']);
    });

    Route::prefix('customer-management')->name('customer-management.')->group(function () {
        Route::resource('/customers', CustomerController::class);
    });

    Route::prefix('appointment-management')->name('appointment-management.')->group(function () {
        Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
        Route::get('/availability/staff', [AppointmentAvailabilityController::class, 'staff'])->name('availability.staff');
        Route::get('/availability/slots', [AppointmentAvailabilityController::class, 'slots'])->name('availability.slots');
        Route::post('/appointments/{appointment}/confirm', [AppointmentStatusController::class, 'confirm'])->name('appointments.confirm');
        Route::post('/appointments/{appointment}/check-in', [AppointmentStatusController::class, 'checkIn'])->name('appointments.check-in');
        Route::post('/appointments/{appointment}/start', [AppointmentStatusController::class, 'start'])->name('appointments.start');
        Route::post('/appointments/{appointment}/complete', [AppointmentStatusController::class, 'complete'])->name('appointments.complete');
        Route::post('/appointments/{appointment}/cancel', [AppointmentStatusController::class, 'cancel'])->name('appointments.cancel');
        Route::post('/appointments/{appointment}/no-show', [AppointmentStatusController::class, 'noShow'])->name('appointments.no-show');
        Route::resource('/appointments', AppointmentController::class);
    });

    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/checkout/appointments/{appointment}', [CheckoutController::class, 'create'])->name('checkout.appointments.create');
        Route::post('/checkout/appointments/{appointment}', [CheckoutController::class, 'store'])->name('checkout.appointments.store');
        Route::get('/invoices/{invoice}/receipt', [InvoiceController::class, 'receipt'])->name('invoices.receipt');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::resource('/invoices', InvoiceController::class)->only(['index', 'show']);
        Route::resource('/payment-methods', PaymentMethodController::class)->except(['show', 'destroy']);
        Route::resource('/payments', PaymentController::class)->only(['index']);
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/dashboard', InventoryDashboardController::class)->name('dashboard');
        Route::get('/current-stock', [CurrentStockController::class, 'index'])->name('stock.index');
        Route::resource('/products', InventoryProductController::class);
        Route::resource('/categories', ProductCategoryController::class)
            ->parameters(['categories' => 'category'])
            ->except(['show']);
        Route::resource('/brands', ProductBrandController::class)
            ->parameters(['brands' => 'brand'])
            ->except(['show']);
        Route::resource('/units', UnitController::class)
            ->except(['show', 'destroy']);
        Route::resource('/movements', StockMovementController::class)
            ->only(['index']);
        Route::resource('/adjustments', StockAdjustmentController::class)
            ->only(['index', 'create', 'store', 'show']);
    });

    Route::prefix('expense-management')->name('expense-management.')->group(function () {
        Route::get('/dashboard', ExpenseDashboardController::class)->name('dashboard');
        Route::get('/reports', [ExpenseReportController::class, 'index'])->name('reports.index');
        Route::post('/expenses/{expense}/approve', [ExpenseApprovalController::class, 'approve'])->name('expenses.approve');
        Route::post('/expenses/{expense}/reject', [ExpenseApprovalController::class, 'reject'])->name('expenses.reject');
        Route::post('/expenses/{expense}/cancel', [ExpenseApprovalController::class, 'cancel'])->name('expenses.cancel');
        Route::post('/expenses/{expense}/payments', [ExpensePaymentController::class, 'store'])->name('expenses.payments.store');
        Route::post('/expenses/{expense}/attachments', [ExpenseAttachmentController::class, 'store'])->name('expenses.attachments.store');
        Route::resource('/expenses', ExpenseController::class)
            ->except(['destroy']);
        Route::resource('/categories', ExpenseCategoryController::class)
            ->parameters(['categories' => 'category'])
            ->except(['show']);
        Route::resource('/vendors', VendorController::class)
            ->except(['show']);
    });

    Route::bind('commission', fn ($value) => \App\Models\StaffCommission::withoutTenantScope()->findOrFail($value));
    Route::bind('payout', fn ($value) => \App\Models\CommissionPayout::withoutTenantScope()->findOrFail($value));
    Route::bind('rule', fn ($value) => \App\Models\CommissionRule::withoutTenantScope()->findOrFail($value));
    Route::bind('setting', fn ($value) => \App\Models\CommissionSetting::withoutTenantScope()->findOrFail($value));
    Route::bind('period', fn ($value) => \App\Models\PayrollPeriod::withoutTenantScope()->findOrFail($value));
    Route::bind('payrollRun', fn ($value) => \App\Models\PayrollRun::withoutTenantScope()->findOrFail($value));
    Route::bind('payrollItem', fn ($value) => \App\Models\PayrollItem::withoutTenantScope()->findOrFail($value));
    Route::bind('salaryStructure', fn ($value) => \App\Models\StaffSalaryStructure::withoutTenantScope()->findOrFail($value));
    Route::bind('program', fn ($value) => \App\Models\LoyaltyProgram::withoutTenantScope()->findOrFail($value));
    Route::bind('earningRule', fn ($value) => \App\Models\LoyaltyEarningRule::withoutTenantScope()->findOrFail($value));
    Route::bind('membershipPlan', fn ($value) => \App\Models\MembershipPlan::withoutTenantScope()->findOrFail($value));
    Route::bind('membership', fn ($value) => \App\Models\CustomerMembership::withoutTenantScope()->findOrFail($value));
    Route::bind('promotion', fn ($value) => \App\Models\Promotion::withoutTenantScope()->findOrFail($value));
    Route::bind('coupon', fn ($value) => \App\Models\PromotionCoupon::withoutTenantScope()->findOrFail($value));

    Route::prefix('commission-management')->name('commission-management.')->group(function () {
        Route::get('/dashboard', CommissionDashboardController::class)->name('dashboard');
        Route::get('/reports', [CommissionReportController::class, 'index'])->name('reports.index');
        Route::get('/settings', [CommissionSettingController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings/{setting}', [CommissionSettingController::class, 'update'])->name('settings.update');
        Route::post('/ledger/{commission}/approve', [StaffCommissionController::class, 'approve'])->name('ledger.approve');
        Route::post('/ledger/{commission}/reject', [StaffCommissionController::class, 'reject'])->name('ledger.reject');
        Route::get('/ledger', [StaffCommissionController::class, 'index'])->name('ledger.index');
        Route::get('/ledger/{commission}', [StaffCommissionController::class, 'show'])->name('ledger.show');
        Route::post('/payouts/{payout}/approve', [CommissionPayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{payout}/pay', [CommissionPayoutController::class, 'pay'])->name('payouts.pay');
        Route::resource('/payouts', CommissionPayoutController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('/rules', CommissionRuleController::class)
            ->parameters(['rules' => 'rule'])
            ->except(['show']);
    });

    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/dashboard', PayrollDashboardController::class)->name('dashboard');
        Route::get('/reports', [PayrollReportController::class, 'index'])->name('reports.index');
        Route::post('/periods/{period}/generate', [PayrollRunController::class, 'generate'])->name('periods.generate');
        Route::post('/runs/{payrollRun}/submit', [PayrollRunController::class, 'submit'])->name('runs.submit');
        Route::post('/runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])->name('runs.approve');
        Route::post('/runs/{payrollRun}/reject', [PayrollRunController::class, 'reject'])->name('runs.reject');
        Route::post('/runs/{payrollRun}/payments', [PayrollPaymentController::class, 'store'])->name('runs.payments.store');
        Route::get('/payslips/{payrollItem}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::resource('/salary-structures', SalaryStructureController::class)
            ->parameters(['salary-structures' => 'salaryStructure'])
            ->except(['show', 'destroy']);
        Route::resource('/periods', PayrollPeriodController::class)
            ->parameters(['periods' => 'period'])
            ->only(['index', 'create', 'store', 'show']);
        Route::resource('/runs', PayrollRunController::class)
            ->parameters(['runs' => 'payrollRun'])
            ->only(['index', 'show']);
    });

    Route::prefix('loyalty-membership')->name('loyalty-management.')->group(function () {
        Route::get('/dashboard', LoyaltyDashboardController::class)->name('dashboard');
        Route::get('/reports', [LoyaltyReportController::class, 'index'])->name('reports.index');
        Route::post('/transactions/adjust', [LoyaltyPointTransactionController::class, 'adjust'])->name('transactions.adjust');
        Route::get('/transactions', [LoyaltyPointTransactionController::class, 'index'])->name('transactions.index');
        Route::post('/memberships/{membership}/cancel', [CustomerMembershipController::class, 'cancel'])->name('memberships.cancel');
        Route::resource('/programs', LoyaltyProgramController::class)
            ->parameters(['programs' => 'program'])
            ->except(['show', 'destroy']);
        Route::resource('/rules', LoyaltyEarningRuleController::class)
            ->parameters(['rules' => 'earningRule'])
            ->except(['show', 'destroy']);
        Route::resource('/membership-plans', MembershipPlanController::class)
            ->parameters(['membership-plans' => 'membershipPlan'])
            ->except(['show', 'destroy']);
        Route::resource('/memberships', CustomerMembershipController::class)
            ->parameters(['memberships' => 'membership'])
            ->only(['index', 'create', 'store', 'show']);
    });

    Route::prefix('promotions-discounts')->name('promotions.')->group(function () {
        Route::get('/dashboard', PromotionDashboardController::class)->name('dashboard');
        Route::get('/reports', [PromotionReportController::class, 'index'])->name('reports.index');
        Route::get('/usage-history', [PromotionUsageController::class, 'index'])->name('usages.index');
        Route::post('/promotions/{promotion}/activate', [PromotionController::class, 'activate'])->name('promotions.activate');
        Route::post('/promotions/{promotion}/deactivate', [PromotionController::class, 'deactivate'])->name('promotions.deactivate');
        Route::resource('/promotions', PromotionController::class);
        Route::resource('/coupons', PromotionCouponController::class)->except(['show', 'destroy']);
    });

});

Route::get('/error', function () {
    abort(500);
});

Route::view('/terms-and-conditions', 'pages.auth.terms')->name('terms');

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';
