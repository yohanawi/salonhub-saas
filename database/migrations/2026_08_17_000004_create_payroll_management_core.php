<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_salary_structures')) {
            Schema::create('staff_salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('salary_type')->default('salary_commission');
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('hourly_rate', 12, 2)->default(0);
                $table->decimal('daily_rate', 12, 2)->default(0);
                $table->boolean('overtime_enabled')->default(false);
                $table->decimal('overtime_rate', 12, 2)->default(0);
                $table->boolean('commission_enabled')->default(true);
                $table->string('payroll_frequency')->default('monthly');
                $table->string('payment_method')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->string('status')->default('active')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'staff_id', 'effective_from'], 'salary_structures_staff_effective_index');
                $table->index(['tenant_id', 'branch_id', 'status'], 'salary_structures_branch_status_index');
            });
        }

        if (! Schema::hasTable('salary_advances')) {
            Schema::create('salary_advances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('advance_number')->nullable();
                $table->decimal('requested_amount', 12, 2)->default(0);
                $table->decimal('approved_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('recovered_amount', 12, 2)->default(0);
                $table->decimal('outstanding_amount', 12, 2)->default(0);
                $table->string('repayment_type')->default('one_time');
                $table->decimal('installment_amount', 12, 2)->nullable();
                $table->date('request_date');
                $table->date('approved_date')->nullable();
                $table->date('paid_date')->nullable();
                $table->text('reason')->nullable();
                $table->string('status')->default('requested')->index();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'advance_number']);
                $table->index(['tenant_id', 'staff_id', 'status'], 'salary_advances_staff_status_index');
            });
        }

        if (! Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->date('pay_date')->nullable();
                $table->string('status')->default('draft')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'branch_id', 'start_date', 'end_date'], 'payroll_period_unique');
                $table->index(['tenant_id', 'status', 'start_date'], 'payroll_period_status_index');
            });
        }

        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
                $table->string('run_number')->nullable();
                $table->string('status')->default('draft')->index();
                $table->unsignedInteger('employees_count')->default(0);
                $table->decimal('basic_salary_total', 12, 2)->default(0);
                $table->decimal('commission_total', 12, 2)->default(0);
                $table->decimal('earnings_total', 12, 2)->default(0);
                $table->decimal('deductions_total', 12, 2)->default(0);
                $table->decimal('gross_pay', 12, 2)->default(0);
                $table->decimal('total_deductions', 12, 2)->default(0);
                $table->decimal('net_pay', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance_amount', 12, 2)->default(0);
                $table->string('payment_status')->default('unpaid')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('calculated_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'run_number']);
                $table->index(['tenant_id', 'branch_id', 'status'], 'payroll_runs_branch_status_index');
            });
        }

        if (! Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->foreignId('salary_structure_id')->nullable()->constrained('staff_salary_structures')->nullOnDelete();
                $table->string('salary_type')->default('monthly');
                $table->decimal('basic_salary_snapshot', 12, 2)->default(0);
                $table->decimal('hourly_rate_snapshot', 12, 2)->default(0);
                $table->decimal('daily_rate_snapshot', 12, 2)->default(0);
                $table->boolean('commission_enabled_snapshot')->default(true);
                $table->decimal('basic_pay', 12, 2)->default(0);
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->decimal('overtime_amount', 12, 2)->default(0);
                $table->decimal('allowance_amount', 12, 2)->default(0);
                $table->decimal('bonus_amount', 12, 2)->default(0);
                $table->decimal('other_earnings', 12, 2)->default(0);
                $table->decimal('gross_pay', 12, 2)->default(0);
                $table->decimal('deduction_amount', 12, 2)->default(0);
                $table->decimal('advance_deduction', 12, 2)->default(0);
                $table->decimal('other_deductions', 12, 2)->default(0);
                $table->decimal('total_deductions', 12, 2)->default(0);
                $table->decimal('net_pay', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance_amount', 12, 2)->default(0);
                $table->string('status')->default('calculated')->index();
                $table->string('payment_status')->default('unpaid')->index();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'payroll_run_id', 'staff_id'], 'payroll_items_run_staff_unique');
                $table->index(['tenant_id', 'staff_id', 'status'], 'payroll_items_staff_status_index');
            });
        }

        if (! Schema::hasTable('payroll_item_lines')) {
            Schema::create('payroll_item_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
                $table->string('line_type');
                $table->string('category');
                $table->string('description')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->nullableMorphs('source');
                $table->json('snapshot')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'line_type', 'category'], 'payroll_lines_type_category_index');
            });
        }

        if (! Schema::hasTable('payroll_payments')) {
            Schema::create('payroll_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payroll_item_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->string('payment_number')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method');
                $table->string('payment_reference')->nullable();
                $table->date('payment_date');
                $table->string('status')->default('paid')->index();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'payment_number']);
                $table->index(['tenant_id', 'branch_id', 'payment_date'], 'payroll_payments_branch_date_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('payroll_item_lines');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('staff_salary_structures');
    }
};
