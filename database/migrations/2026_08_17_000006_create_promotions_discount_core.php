<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('promotion_type')->default('standard');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('maximum_discount_amount', 12, 2)->nullable();
            $table->string('application_type')->default('automatic');
            $table->boolean('coupon_required')->default(false);
            $table->decimal('minimum_spend', 12, 2)->default(0);
            $table->unsignedInteger('minimum_quantity')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->string('customer_scope')->default('all');
            $table->string('branch_scope')->default('all');
            $table->string('target_scope')->default('invoice');
            $table->boolean('is_stackable')->default(false);
            $table->unsignedInteger('priority')->default(100);
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'starts_at', 'ends_at'], 'promotions_tenant_status_period_index');
            $table->index(['tenant_id', 'application_type']);
        });

        Schema::create('promotion_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'promotion_id', 'branch_id'], 'promotion_branch_unique');
        });

        Schema::create('promotion_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'promotion_id', 'service_id'], 'promotion_service_unique');
        });

        Schema::create('promotion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'promotion_id', 'product_id'], 'promotion_product_unique');
        });

        Schema::create('promotion_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'promotion_id', 'customer_id'], 'promotion_customer_unique');
        });

        Schema::create('promotion_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'promotion_id', 'membership_plan_id'], 'promotion_membership_unique');
        });

        Schema::create('promotion_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'promotion_id', 'status'], 'promotion_coupons_promotion_status_index');
        });

        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('status')->default('used')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'promotion_id', 'used_at'], 'promotion_usage_promotion_used_index');
            $table->index(['tenant_id', 'customer_id', 'used_at'], 'promotion_usage_customer_used_index');
            $table->unique(['tenant_id', 'invoice_id', 'promotion_id'], 'promotion_usage_invoice_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'promotion_id')) {
                $table->foreignId('promotion_id')->nullable()->after('customer_membership_id')->constrained('promotions')->nullOnDelete();
            }

            if (! Schema::hasColumn('sales', 'promotion_coupon_id')) {
                $table->foreignId('promotion_coupon_id')->nullable()->after('promotion_id')->constrained('promotion_coupons')->nullOnDelete();
            }

            if (! Schema::hasColumn('sales', 'promotion_name')) {
                $table->string('promotion_name')->nullable()->after('promotion_coupon_id');
            }

            if (! Schema::hasColumn('sales', 'promotion_coupon_code')) {
                $table->string('promotion_coupon_code')->nullable()->after('promotion_name');
            }

            if (! Schema::hasColumn('sales', 'promotion_discount_type')) {
                $table->string('promotion_discount_type')->nullable()->after('promotion_coupon_code');
            }

            if (! Schema::hasColumn('sales', 'promotion_discount_value')) {
                $table->decimal('promotion_discount_value', 12, 2)->default(0)->after('promotion_discount_type');
            }

            if (! Schema::hasColumn('sales', 'promotion_discount_amount')) {
                $table->decimal('promotion_discount_amount', 12, 2)->default(0)->after('promotion_discount_value');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'promotion_id')) {
                $table->foreignId('promotion_id')->nullable()->after('membership_benefit_id')->constrained('promotions')->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'promotion_discount_amount')) {
                $table->decimal('promotion_discount_amount', 12, 2)->default(0)->after('membership_discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'promotion_id')) {
                $table->dropConstrainedForeignId('promotion_id');
            }

            if (Schema::hasColumn('sale_items', 'promotion_discount_amount')) {
                $table->dropColumn('promotion_discount_amount');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach (['promotion_coupon_id', 'promotion_id'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['promotion_discount_amount', 'promotion_discount_value', 'promotion_discount_type', 'promotion_coupon_code', 'promotion_name'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('promotion_coupons');
        Schema::dropIfExists('promotion_memberships');
        Schema::dropIfExists('promotion_customers');
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('promotion_services');
        Schema::dropIfExists('promotion_branches');
        Schema::dropIfExists('promotions');
    }
};
