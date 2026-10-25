<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // Payroll: EPF/ETF, bank details for transfer files, payslip delivery
        // ------------------------------------------------------------------
        Schema::table('staff_salary_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_salary_structures', 'epf_applicable')) {
                $table->boolean('epf_applicable')->default(true)->after('commission_enabled');
                $table->string('epf_number')->nullable()->after('epf_applicable');
                $table->string('bank_code', 10)->nullable()->after('bank_name');
                $table->string('bank_branch_code', 10)->nullable()->after('bank_code');
            }
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_items', 'epf_employee_amount')) {
                $table->decimal('statutory_earnings', 12, 2)->default(0)->after('gross_pay');
                $table->decimal('epf_employee_amount', 12, 2)->default(0)->after('advance_deduction');
                $table->decimal('epf_employer_amount', 12, 2)->default(0)->after('epf_employee_amount');
                $table->decimal('etf_employer_amount', 12, 2)->default(0)->after('epf_employer_amount');
                $table->timestamp('payslip_sent_at')->nullable();
            }
        });

        Schema::table('salary_advances', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_advances', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reason');
                $table->string('payment_method')->nullable()->after('paid_date');
                $table->string('payment_reference')->nullable()->after('payment_method');
            }
        });

        // ------------------------------------------------------------------
        // Loyalty tiers & membership renewals
        // ------------------------------------------------------------------
        if (! Schema::hasTable('loyalty_tiers')) {
            Schema::create('loyalty_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('min_points')->default(0);
                $table->decimal('points_multiplier', 5, 2)->default(1);
                $table->string('color', 20)->default('primary');
                $table->text('benefits')->nullable();
                $table->timestamps();

                $table->unique(['loyalty_program_id', 'name']);
                $table->index(['loyalty_program_id', 'min_points']);
            });
        }

        Schema::table('customer_loyalty_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_loyalty_accounts', 'loyalty_tier_id')) {
                $table->foreignId('loyalty_tier_id')->nullable()->after('loyalty_program_id')->constrained('loyalty_tiers')->nullOnDelete();
                $table->timestamp('tier_achieved_at')->nullable()->after('loyalty_tier_id');
            }
        });

        Schema::table('customer_memberships', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_memberships', 'renewal_reminder_sent_at')) {
                $table->timestamp('renewal_reminder_sent_at')->nullable()->after('auto_renew');
                $table->foreignId('renewed_from_membership_id')->nullable()->after('renewal_reminder_sent_at')->constrained('customer_memberships')->nullOnDelete();
                $table->foreignId('renewed_membership_id')->nullable()->after('renewed_from_membership_id')->constrained('customer_memberships')->nullOnDelete();
            }
        });

        // ------------------------------------------------------------------
        // Promotions: buy X get Y, time windows, birthday offers
        // ------------------------------------------------------------------
        Schema::table('promotions', function (Blueprint $table) {
            if (! Schema::hasColumn('promotions', 'buy_quantity')) {
                $table->unsignedInteger('buy_quantity')->nullable()->after('promotion_type');
                $table->unsignedInteger('get_quantity')->nullable()->after('buy_quantity');
                $table->decimal('get_discount_percentage', 5, 2)->nullable()->after('get_quantity');
                $table->json('active_days')->nullable()->after('ends_at');
                $table->time('active_from_time')->nullable()->after('active_days');
                $table->time('active_to_time')->nullable()->after('active_from_time');
                $table->string('birthday_window', 10)->nullable()->after('active_to_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'buy_quantity')) {
                $table->dropColumn(['buy_quantity', 'get_quantity', 'get_discount_percentage', 'active_days', 'active_from_time', 'active_to_time', 'birthday_window']);
            }
        });

        Schema::table('customer_memberships', function (Blueprint $table) {
            if (Schema::hasColumn('customer_memberships', 'renewal_reminder_sent_at')) {
                $table->dropConstrainedForeignId('renewed_membership_id');
                $table->dropConstrainedForeignId('renewed_from_membership_id');
                $table->dropColumn('renewal_reminder_sent_at');
            }
        });

        Schema::table('customer_loyalty_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('customer_loyalty_accounts', 'loyalty_tier_id')) {
                $table->dropConstrainedForeignId('loyalty_tier_id');
                $table->dropColumn('tier_achieved_at');
            }
        });

        Schema::dropIfExists('loyalty_tiers');

        Schema::table('salary_advances', function (Blueprint $table) {
            if (Schema::hasColumn('salary_advances', 'rejection_reason')) {
                $table->dropColumn(['rejection_reason', 'payment_method', 'payment_reference']);
            }
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_items', 'epf_employee_amount')) {
                $table->dropColumn(['statutory_earnings', 'epf_employee_amount', 'epf_employer_amount', 'etf_employer_amount', 'payslip_sent_at']);
            }
        });

        Schema::table('staff_salary_structures', function (Blueprint $table) {
            if (Schema::hasColumn('staff_salary_structures', 'epf_applicable')) {
                $table->dropColumn(['epf_applicable', 'epf_number', 'bank_code', 'bank_branch_code']);
            }
        });
    }
};
