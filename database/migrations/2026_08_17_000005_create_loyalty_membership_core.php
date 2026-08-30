<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loyalty_programs')) {
            Schema::create('loyalty_programs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('active')->index();
                $table->unsignedInteger('points_expiry_days')->nullable();
                $table->unsignedInteger('minimum_redeem_points')->default(0);
                $table->decimal('maximum_redeem_percentage', 5, 2)->default(30);
                $table->boolean('allow_partial_redemption')->default(true);
                $table->boolean('allow_points_on_discounted_sales')->default(false);
                $table->unsignedInteger('redemption_points')->default(100);
                $table->decimal('redemption_value', 12, 2)->default(500);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('loyalty_earning_rules')) {
            Schema::create('loyalty_earning_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('rule_type')->default('spend');
                $table->decimal('spend_amount', 12, 2)->default(100);
                $table->unsignedInteger('points_awarded')->default(1);
                $table->decimal('minimum_purchase_amount', 12, 2)->default(0);
                $table->unsignedInteger('maximum_points_per_transaction')->nullable();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedInteger('priority')->default(100);
                $table->string('status')->default('active')->index();
                $table->timestamps();

                $table->index(['tenant_id', 'loyalty_program_id', 'status'], 'loyalty_rules_program_status_index');
            });
        }

        if (! Schema::hasTable('customer_loyalty_accounts')) {
            Schema::create('customer_loyalty_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
                $table->integer('available_points')->default(0);
                $table->integer('pending_points')->default(0);
                $table->integer('lifetime_earned_points')->default(0);
                $table->integer('lifetime_redeemed_points')->default(0);
                $table->integer('expired_points')->default(0);
                $table->string('status')->default('active')->index();
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'customer_id'], 'customer_loyalty_account_unique');
                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('loyalty_point_transactions')) {
            Schema::create('loyalty_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('loyalty_account_id')->constrained('customer_loyalty_accounts')->cascadeOnDelete();
                $table->string('type')->index();
                $table->integer('points');
                $table->integer('balance_before')->default(0);
                $table->integer('balance_after')->default(0);
                $table->nullableMorphs('source');
                $table->text('description')->nullable();
                $table->timestamp('earned_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'customer_id', 'created_at'], 'loyalty_points_customer_created_index');
            });
        }

        if (! Schema::hasTable('membership_plans')) {
            Schema::create('membership_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('duration_type')->default('months');
                $table->unsignedInteger('duration_value')->default(12);
                $table->string('billing_type')->default('one_time');
                $table->decimal('joining_fee', 12, 2)->default(0);
                $table->string('status')->default('active')->index();
                $table->boolean('is_featured')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('membership_benefits')) {
            Schema::create('membership_benefits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
                $table->string('benefit_type');
                $table->string('discount_type')->nullable();
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->string('usage_limit_period')->nullable();
                $table->decimal('loyalty_multiplier', 8, 2)->nullable();
                $table->unsignedInteger('priority')->default(100);
                $table->string('status')->default('active')->index();
                $table->timestamps();

                $table->index(['tenant_id', 'membership_plan_id', 'status'], 'membership_benefits_plan_status_index');
            });
        }

        if (! Schema::hasTable('customer_memberships')) {
            Schema::create('customer_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
                $table->string('membership_number')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('pending')->index();
                $table->decimal('price_paid', 12, 2)->default(0);
                $table->decimal('joining_fee_paid', 12, 2)->default(0);
                $table->boolean('auto_renew')->default(false);
                $table->foreignId('invoice_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancellation_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'membership_number']);
                $table->index(['tenant_id', 'customer_id', 'status'], 'customer_memberships_customer_status_index');
            });
        }

        if (! Schema::hasTable('membership_benefit_usages')) {
            Schema::create('membership_benefit_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_membership_id')->constrained()->cascadeOnDelete();
                $table->foreignId('membership_benefit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->foreignId('invoice_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->timestamp('used_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'customer_id', 'used_at'], 'membership_usage_customer_used_index');
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'loyalty_account_id')) {
                $table->foreignId('loyalty_account_id')->nullable()->after('appointment_id')->constrained('customer_loyalty_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('sales', 'customer_membership_id')) {
                $table->foreignId('customer_membership_id')->nullable()->after('loyalty_account_id')->constrained('customer_memberships')->nullOnDelete();
            }

            if (! Schema::hasColumn('sales', 'membership_discount_amount')) {
                $table->decimal('membership_discount_amount', 12, 2)->default(0)->after('discount');
            }

            if (! Schema::hasColumn('sales', 'loyalty_redemption_amount')) {
                $table->decimal('loyalty_redemption_amount', 12, 2)->default(0)->after('membership_discount_amount');
            }

            if (! Schema::hasColumn('sales', 'loyalty_points_redeemed')) {
                $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('loyalty_redemption_amount');
            }

            if (! Schema::hasColumn('sales', 'loyalty_points_earned')) {
                $table->unsignedInteger('loyalty_points_earned')->default(0)->after('loyalty_points_redeemed');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'customer_membership_id')) {
                $table->foreignId('customer_membership_id')->nullable()->after('staff_id')->constrained('customer_memberships')->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'service_category_id')) {
                $table->foreignId('service_category_id')->nullable()->after('service_id')->constrained('service_categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'membership_benefit_id')) {
                $table->foreignId('membership_benefit_id')->nullable()->after('customer_membership_id')->constrained('membership_benefits')->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'membership_discount_amount')) {
                $table->decimal('membership_discount_amount', 12, 2)->default(0)->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            foreach (['membership_benefit_id', 'service_category_id', 'customer_membership_id'] as $column) {
                if (Schema::hasColumn('sale_items', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            if (Schema::hasColumn('sale_items', 'membership_discount_amount')) {
                $table->dropColumn('membership_discount_amount');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach (['customer_membership_id', 'loyalty_account_id'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['membership_discount_amount', 'loyalty_redemption_amount', 'loyalty_points_redeemed', 'loyalty_points_earned'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('membership_benefit_usages');
        Schema::dropIfExists('customer_memberships');
        Schema::dropIfExists('membership_benefits');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('loyalty_point_transactions');
        Schema::dropIfExists('customer_loyalty_accounts');
        Schema::dropIfExists('loyalty_earning_rules');
        Schema::dropIfExists('loyalty_programs');
    }
};
