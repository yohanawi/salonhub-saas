<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('cash');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_reference')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status')->index();
            }

            if (! Schema::hasColumn('sales', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            }

            if (! Schema::hasColumn('sales', 'balance_amount')) {
                $table->decimal('balance_amount', 12, 2)->default(0)->after('paid_amount');
            }

            if (! Schema::hasColumn('sales', 'issued_at')) {
                $table->timestamp('issued_at')->nullable()->after('balance_amount');
            }

            if (! Schema::hasColumn('sales', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('issued_at');
            }

            if (! Schema::hasColumn('sales', 'notes')) {
                $table->text('notes')->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('sales', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('sales', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('sales', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
            }

            if (! Schema::hasColumn('sales', 'void_reason')) {
                $table->text('void_reason')->nullable();
            }
        });

        DB::table('sales')
            ->whereNull('payment_status')
            ->update(['payment_status' => 'unpaid']);

        Schema::table('sales', function (Blueprint $table) {
            foreach ([
                'sales_tenant_branch_status_index' => ['tenant_id', 'branch_id', 'status'],
                'sales_tenant_payment_status_index' => ['tenant_id', 'payment_status'],
                'sales_tenant_issued_at_index' => ['tenant_id', 'issued_at'],
            ] as $index => $columns) {
                if (! $this->indexExists('sales', $index)) {
                    $table->index($columns, $index);
                }
            }

            if (! $this->indexExists('sales', 'sales_tenant_appointment_unique')) {
                $table->unique(['tenant_id', 'appointment_id'], 'sales_tenant_appointment_unique');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('service_id')->constrained('products')->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'item_name')) {
                $table->string('item_name')->nullable()->after('product_id');
            }

            if (! Schema::hasColumn('sale_items', 'gross_amount')) {
                $table->decimal('gross_amount', 12, 2)->default(0)->after('unit_price');
            }

            if (! Schema::hasColumn('sale_items', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('gross_amount');
            }

            if (! Schema::hasColumn('sale_items', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('sale_items', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('sale_items', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('net_amount');
            }
        });

        DB::table('sale_items')
            ->whereNull('item_name')
            ->update(['item_name' => DB::raw('description')]);

        DB::table('sale_items')
            ->update([
                'gross_amount' => DB::raw('quantity * unit_price'),
                'discount_amount' => DB::raw('discount'),
                'tax_amount' => DB::raw('0'),
                'net_amount' => DB::raw('total'),
                'total_amount' => DB::raw('total'),
            ]);

        Schema::table('sale_items', function (Blueprint $table) {
            foreach ([
                'sale_items_tenant_service_index' => ['tenant_id', 'service_id'],
                'sale_items_tenant_staff_index' => ['tenant_id', 'staff_id'],
            ] as $index => $columns) {
                if (! $this->indexExists('sale_items', $index)) {
                    $table->index($columns, $index);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'payment_number')) {
                $table->string('payment_number')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('payments', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('payment_number')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'transaction_reference')) {
                $table->string('transaction_reference')->nullable()->after('reference');
            }

            if (! Schema::hasColumn('payments', 'gateway_reference')) {
                $table->string('gateway_reference')->nullable()->after('transaction_reference');
            }

            if (! Schema::hasColumn('payments', 'cash_received')) {
                $table->decimal('cash_received', 12, 2)->nullable()->after('gateway_reference');
            }

            if (! Schema::hasColumn('payments', 'change_given')) {
                $table->decimal('change_given', 12, 2)->nullable()->after('cash_received');
            }

            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('received_by');
            }
        });

        DB::table('payments')
            ->whereNull('payment_number')
            ->orderBy('id')
            ->get(['id', 'paid_at'])
            ->each(function ($payment) {
                $prefix = 'PAY-' . ($payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at) : now())->format('Y');

                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update([
                        'payment_number' => $prefix . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });

        DB::table('payments')
            ->where('status', 'paid')
            ->update(['status' => 'completed']);

        Schema::table('payments', function (Blueprint $table) {
            foreach ([
                'payments_tenant_payment_number_unique' => ['tenant_id', 'payment_number'],
                'payments_tenant_branch_paid_at_index' => ['tenant_id', 'branch_id', 'paid_at'],
                'payments_tenant_status_index' => ['tenant_id', 'status'],
            ] as $index => $columns) {
                if (! $this->indexExists('payments', $index)) {
                    $index === 'payments_tenant_payment_number_unique'
                        ? $table->unique($columns, $index)
                        : $table->index($columns, $index);
                }
            }
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('refund_number')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'refund_number']);
            $table->index(['tenant_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');

        Schema::table('payments', function (Blueprint $table) {
            foreach ([
                'payments_tenant_status_index',
                'payments_tenant_branch_paid_at_index',
                'payments_tenant_payment_number_unique',
            ] as $index) {
                if ($this->indexExists('payments', $index)) {
                    str_contains($index, 'unique')
                        ? $table->dropUnique($index)
                        : $table->dropIndex($index);
                }
            }

            foreach (['payment_method_id', 'customer_id', 'branch_id'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['notes', 'change_given', 'cash_received', 'gateway_reference', 'transaction_reference', 'payment_number'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            foreach (['sale_items_tenant_staff_index', 'sale_items_tenant_service_index'] as $index) {
                if ($this->indexExists('sale_items', $index)) {
                    $table->dropIndex($index);
                }
            }

            foreach (['product_id', 'service_id'] as $column) {
                if (Schema::hasColumn('sale_items', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['total_amount', 'net_amount', 'tax_amount', 'discount_amount', 'gross_amount', 'item_name'] as $column) {
                if (Schema::hasColumn('sale_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach ([
                'sales_tenant_appointment_unique',
                'sales_tenant_issued_at_index',
                'sales_tenant_payment_status_index',
                'sales_tenant_branch_status_index',
            ] as $index) {
                if ($this->indexExists('sales', $index)) {
                    str_contains($index, 'unique')
                        ? $table->dropUnique($index)
                        : $table->dropIndex($index);
                }
            }

            foreach (['voided_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['void_reason', 'voided_at', 'notes', 'paid_at', 'issued_at', 'balance_amount', 'paid_amount', 'payment_status'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('payment_methods');
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing) => $existing['name'] === $index);
    }
};
