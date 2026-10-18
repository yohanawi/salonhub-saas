<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tips are collected with the invoice but are not sales revenue, so they sit outside `total`.
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'tip_amount')) {
                $table->decimal('tip_amount', 12, 2)->default(0)->after('total');
            }

            if (! Schema::hasColumn('sales', 'package_discount_amount')) {
                $table->decimal('package_discount_amount', 12, 2)->default(0)->after('membership_discount_amount');
            }
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_items', 'tip_amount')) {
                $table->decimal('tip_amount', 12, 2)->default(0)->after('commission_amount');
            }
        });

        if (! Schema::hasTable('staff_tips')) {
            Schema::create('staff_tips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('invoice_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('status', 20)->default('pending')->index();
                $table->timestamp('earned_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'staff_id', 'status', 'earned_at']);
            });
        }

        if (! Schema::hasTable('gift_cards')) {
            Schema::create('gift_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code', 40);
                $table->decimal('initial_amount', 12, 2);
                $table->decimal('balance', 12, 2);
                $table->string('status', 20)->default('active')->index();
                $table->date('expires_at')->nullable();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_phone', 30)->nullable();
                $table->text('message')->nullable();
                $table->foreignId('invoice_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('void_reason')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
            });
        }

        if (! Schema::hasTable('gift_card_transactions')) {
            Schema::create('gift_card_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gift_card_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_after', 12, 2);
                $table->foreignId('invoice_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('appointment_deposits')) {
            Schema::create('appointment_deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('deposit_number', 40)->nullable();
                $table->decimal('amount', 12, 2);
                $table->decimal('applied_amount', 12, 2)->default(0);
                $table->decimal('refunded_amount', 12, 2)->default(0);
                $table->string('status', 20)->default('held')->index();
                $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('cash_register_shift_id')->nullable()->constrained()->nullOnDelete();
                $table->string('transaction_reference')->nullable();
                $table->foreignId('applied_invoice_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('cashier_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'gift_card_id')) {
                $table->foreignId('gift_card_id')->nullable()->after('payment_method_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'appointment_deposit_id')) {
                $table->foreignId('appointment_deposit_id')->nullable()->after('gift_card_id')->constrained()->nullOnDelete();
            }
        });

        if (! Schema::hasTable('service_packages')) {
            Schema::create('service_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2);
                $table->unsignedInteger('validity_days')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_package_items')) {
            Schema::create('service_package_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_package_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_packages')) {
            Schema::create('customer_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
                $table->string('package_number', 40)->nullable();
                $table->string('name');
                $table->decimal('price_paid', 12, 2);
                $table->timestamp('purchased_at');
                $table->date('expires_at')->nullable();
                $table->string('status', 20)->default('active')->index();
                $table->foreignId('invoice_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_package_items')) {
            Schema::create('customer_package_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_package_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->string('service_name');
                $table->unsignedInteger('total_quantity');
                $table->unsignedInteger('used_quantity')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_package_usages')) {
            Schema::create('customer_package_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_package_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('sales')->cascadeOnDelete();
                $table->unsignedBigInteger('invoice_item_id')->nullable()->index();
                $table->unsignedInteger('quantity');
                $table->decimal('value', 12, 2)->default(0);
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'customer_package_item_id')) {
                $table->foreignId('customer_package_item_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('package_quantity')->default(0);
                $table->decimal('package_discount_amount', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'customer_package_item_id')) {
                $table->dropConstrainedForeignId('customer_package_item_id');
                $table->dropColumn(['package_quantity', 'package_discount_amount']);
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'appointment_deposit_id')) {
                $table->dropConstrainedForeignId('appointment_deposit_id');
            }

            if (Schema::hasColumn('payments', 'gift_card_id')) {
                $table->dropConstrainedForeignId('gift_card_id');
            }
        });

        Schema::dropIfExists('customer_package_usages');
        Schema::dropIfExists('customer_package_items');
        Schema::dropIfExists('customer_packages');
        Schema::dropIfExists('service_package_items');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('appointment_deposits');
        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('staff_tips');

        Schema::table('payroll_items', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_items', 'tip_amount')) {
                $table->dropColumn('tip_amount');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach (['tip_amount', 'package_discount_amount'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
