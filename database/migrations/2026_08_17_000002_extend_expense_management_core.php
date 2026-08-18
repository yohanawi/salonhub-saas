<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('tenant_id')->constrained('expense_categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('expense_categories', 'code')) {
                $table->string('code')->nullable()->after('name');
            }

            if (! Schema::hasColumn('expense_categories', 'description')) {
                $table->text('description')->nullable()->after('code');
            }

            if (! Schema::hasColumn('expense_categories', 'color')) {
                $table->string('color', 30)->nullable()->after('description');
            }

            if (! Schema::hasColumn('expense_categories', 'icon')) {
                $table->string('icon', 80)->nullable()->after('color');
            }

            if (! Schema::hasColumn('expense_categories', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        if (! Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('tax_number')->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'name']);
                $table->index(['tenant_id', 'is_active']);
            });
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'expense_number')) {
                $table->string('expense_number')->nullable()->after('branch_id');
            }

            if (! Schema::hasColumn('expenses', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('category_id')->constrained('vendors')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('description');
            }

            if (! Schema::hasColumn('expenses', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('reference_number');
            }

            if (! Schema::hasColumn('expenses', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
            }

            if (! Schema::hasColumn('expenses', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('expenses', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('expenses', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            }

            if (! Schema::hasColumn('expenses', 'balance_amount')) {
                $table->decimal('balance_amount', 12, 2)->default(0)->after('paid_amount');
            }

            if (! Schema::hasColumn('expenses', 'currency')) {
                $table->string('currency', 10)->default('LKR')->after('balance_amount');
            }

            if (! Schema::hasColumn('expenses', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('currency')->index();
            }

            if (! Schema::hasColumn('expenses', 'approval_status')) {
                $table->string('approval_status')->default('pending')->after('payment_status')->index();
            }

            if (! Schema::hasColumn('expenses', 'expense_status')) {
                $table->string('expense_status')->default('confirmed')->after('approval_status')->index();
            }

            if (! Schema::hasColumn('expenses', 'notes')) {
                $table->text('notes')->nullable()->after('expense_status');
            }

            if (! Schema::hasColumn('expenses', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('expenses', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }

            if (! Schema::hasColumn('expenses', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
            }

            if (! Schema::hasColumn('expenses', 'source_type')) {
                $table->nullableMorphs('source');
            }

            if (! Schema::hasColumn('expenses', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        DB::table('expenses')
            ->whereNull('expense_number')
            ->orderBy('id')
            ->each(function ($expense) {
                DB::table('expenses')
                    ->where('id', $expense->id)
                    ->update([
                        'expense_number' => 'EXP-' . now()->format('Y') . '-' . str_pad((string) $expense->id, 6, '0', STR_PAD_LEFT),
                        'subtotal' => $expense->amount,
                        'total_amount' => $expense->amount,
                        'balance_amount' => $expense->amount,
                        'reference_number' => $expense->reference,
                    ]);
            });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unique(['tenant_id', 'expense_number'], 'expenses_tenant_number_unique');
            $table->index(['tenant_id', 'branch_id', 'expense_date'], 'expenses_branch_date_index');
        });

        if (! Schema::hasTable('expense_payments')) {
            Schema::create('expense_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method');
                $table->date('payment_date');
                $table->string('reference_number')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'branch_id', 'payment_date']);
            });
        }

        if (! Schema::hasTable('expense_attachments')) {
            Schema::create('expense_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('expense_payments');
        Schema::dropIfExists('vendors');
    }
};
