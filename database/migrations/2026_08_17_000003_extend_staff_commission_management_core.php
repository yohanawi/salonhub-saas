<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commission_settings')) {
            Schema::create('commission_settings', function (Blueprint $table) {
                $table->foreignId('tenant_id')->primary()->constrained()->cascadeOnDelete();
                $table->boolean('commission_enabled')->default(true);
                $table->string('default_service_commission_type')->default('percentage');
                $table->decimal('default_service_commission_value', 8, 2)->default(0);
                $table->string('default_product_commission_type')->default('none');
                $table->decimal('default_product_commission_value', 8, 2)->default(0);
                $table->string('calculation_basis')->default('net_after_discount');
                $table->string('earn_trigger')->default('invoice_paid');
                $table->boolean('requires_approval')->default(true);
                $table->boolean('allow_manual_adjustment')->default(false);
                $table->boolean('allow_negative_commission')->default(false);
                $table->string('refund_behavior')->default('reverse');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commission_rules')) {
            Schema::create('commission_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->nullable()->constrained('staff')->cascadeOnDelete();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('commission_scope');
                $table->string('commission_type')->default('percentage');
                $table->decimal('commission_value', 8, 2)->default(0);
                $table->string('calculate_on')->default('net_after_discount');
                $table->unsignedInteger('priority')->default(100);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'commission_scope', 'is_active'], 'commission_rules_scope_index');
                $table->index(['tenant_id', 'staff_id', 'service_id', 'product_id'], 'commission_rules_item_index');
            });
        }

        Schema::table('staff_commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_commissions', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('staff_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('appointment_id')->constrained('sales')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'invoice_item_id')) {
                $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('sale_items')->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'commission_rule_id')) {
                $table->foreignId('commission_rule_id')->nullable()->after('sale_item_id')->constrained('commission_rules')->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('commission_rule_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('service_id')->constrained('products')->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'source_type')) {
                $table->nullableMorphs('source');
            }

            if (! Schema::hasColumn('staff_commissions', 'gross_amount')) {
                $table->decimal('gross_amount', 12, 2)->default(0)->after('source_id');
            }

            if (! Schema::hasColumn('staff_commissions', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('gross_amount');
            }

            if (! Schema::hasColumn('staff_commissions', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('staff_commissions', 'commission_base')) {
                $table->decimal('commission_base', 12, 2)->default(0)->after('base_amount');
            }

            if (! Schema::hasColumn('staff_commissions', 'earned_at')) {
                $table->timestamp('earned_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('staff_commissions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('earned_at');
            }

            if (! Schema::hasColumn('staff_commissions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('staff_commissions', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'reversed_commission_id')) {
                $table->foreignId('reversed_commission_id')->nullable()->after('paid_by')->constrained('staff_commissions')->nullOnDelete();
            }

            if (! Schema::hasColumn('staff_commissions', 'notes')) {
                $table->text('notes')->nullable()->after('reversed_commission_id');
            }
        });

        DB::table('staff_commissions')
            ->whereNull('invoice_id')
            ->update([
                'invoice_id' => DB::raw('sale_id'),
                'invoice_item_id' => DB::raw('sale_item_id'),
                'commission_base' => DB::raw('base_amount'),
                'gross_amount' => DB::raw('base_amount'),
                'net_amount' => DB::raw('base_amount'),
            ]);

        Schema::table('staff_commissions', function (Blueprint $table) {
            foreach ([
                'staff_commissions_branch_status_index' => ['tenant_id', 'branch_id', 'status'],
                'staff_commissions_staff_earned_index' => ['tenant_id', 'staff_id', 'earned_at'],
            ] as $index => $columns) {
                if (! $this->indexExists('staff_commissions', $index)) {
                    $table->index($columns, $index);
                }
            }

            if (! $this->indexExists('staff_commissions', 'staff_commissions_item_staff_unique')) {
                $table->unique(['tenant_id', 'sale_item_id', 'staff_id'], 'staff_commissions_item_staff_unique');
            }
        });

        if (! Schema::hasTable('commission_payouts')) {
            Schema::create('commission_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('payout_number')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('gross_commission', 12, 2)->default(0);
                $table->decimal('adjustment_amount', 12, 2)->default(0);
                $table->decimal('net_payable', 12, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->string('status')->default('pending')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'payout_number']);
                $table->index(['tenant_id', 'staff_id', 'period_start', 'period_end'], 'commission_payouts_staff_period_index');
            });
        }

        if (! Schema::hasTable('commission_payout_items')) {
            Schema::create('commission_payout_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('commission_payout_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_commission_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['commission_payout_id', 'staff_commission_id'], 'commission_payout_items_unique');
            });
        }

        if (! Schema::hasTable('commission_adjustments')) {
            Schema::create('commission_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->foreignId('commission_payout_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type')->default('manual');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'staff_id', 'created_at'], 'commission_adjustments_staff_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_adjustments');
        Schema::dropIfExists('commission_payout_items');
        Schema::dropIfExists('commission_payouts');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('commission_settings');
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing) => $existing['name'] === $index);
    }
};
