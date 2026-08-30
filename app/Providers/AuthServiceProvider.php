<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\CommissionPayout;
use App\Models\CommissionRule;
use App\Models\CommissionSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\StaffSalaryStructure;
use App\Models\Invoice;
use App\Models\CustomerMembership;
use App\Models\LoyaltyEarningRule;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyProgram;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\PromotionUsage;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Vendor;
use App\Policies\AppointmentPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CommissionPayoutPolicy;
use App\Policies\CommissionRulePolicy;
use App\Policies\CommissionSettingPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\CustomerMembershipPolicy;
use App\Policies\LoyaltyEarningRulePolicy;
use App\Policies\LoyaltyPointTransactionPolicy;
use App\Policies\LoyaltyProgramPolicy;
use App\Policies\MembershipPlanPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PayrollItemPolicy;
use App\Policies\PayrollPeriodPolicy;
use App\Policies\PayrollRunPolicy;
use App\Policies\PromotionCouponPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\PromotionUsagePolicy;
use App\Policies\ProductBrandPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\ServicePolicy;
use App\Policies\SettingPolicy;
use App\Policies\StaffPolicy;
use App\Policies\StaffCommissionPolicy;
use App\Policies\StaffSalaryStructurePolicy;
use App\Policies\StockAdjustmentPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\UnitPolicy;
use App\Policies\VendorPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Branch::class => BranchPolicy::class,
        CommissionPayout::class => CommissionPayoutPolicy::class,
        CommissionRule::class => CommissionRulePolicy::class,
        CommissionSetting::class => CommissionSettingPolicy::class,
        Customer::class => CustomerPolicy::class,
        CustomerMembership::class => CustomerMembershipPolicy::class,
        Expense::class => ExpensePolicy::class,
        ExpenseCategory::class => ExpenseCategoryPolicy::class,
        Invoice::class => InvoicePolicy::class,
        LoyaltyEarningRule::class => LoyaltyEarningRulePolicy::class,
        LoyaltyPointTransaction::class => LoyaltyPointTransactionPolicy::class,
        LoyaltyProgram::class => LoyaltyProgramPolicy::class,
        MembershipPlan::class => MembershipPlanPolicy::class,
        Payment::class => PaymentPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        PayrollItem::class => PayrollItemPolicy::class,
        PayrollPeriod::class => PayrollPeriodPolicy::class,
        PayrollRun::class => PayrollRunPolicy::class,
        Promotion::class => PromotionPolicy::class,
        PromotionCoupon::class => PromotionCouponPolicy::class,
        PromotionUsage::class => PromotionUsagePolicy::class,
        Product::class => ProductPolicy::class,
        ProductBrand::class => ProductBrandPolicy::class,
        ProductCategory::class => ProductCategoryPolicy::class,
        Service::class => ServicePolicy::class,
        ServiceCategory::class => ServiceCategoryPolicy::class,
        Setting::class => SettingPolicy::class,
        Staff::class => StaffPolicy::class,
        StaffCommission::class => StaffCommissionPolicy::class,
        StaffSalaryStructure::class => StaffSalaryStructurePolicy::class,
        StockAdjustment::class => StockAdjustmentPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        Unit::class => UnitPolicy::class,
        Vendor::class => VendorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
