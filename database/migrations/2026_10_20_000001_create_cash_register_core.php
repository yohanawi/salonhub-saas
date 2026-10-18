<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('shift_number')->nullable();
            $table->string('status')->default('open');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('cash_difference', 12, 2)->nullable();
            $table->json('summary')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->text('opening_notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'shift_number']);
            $table->index(['tenant_id', 'branch_id', 'status']);
            $table->index(['tenant_id', 'branch_id', 'opened_at']);
        });

        Schema::create('cash_register_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_shift_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cash_register_shift_id')) {
                $table->foreignId('cash_register_shift_id')->nullable()->after('payment_method_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'cash_register_shift_id')) {
                $table->foreignId('cash_register_shift_id')->nullable()->after('payment_method_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        foreach (['refunds', 'payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'cash_register_shift_id')) {
                    $table->dropConstrainedForeignId('cash_register_shift_id');
                }
            });
        }

        Schema::dropIfExists('cash_register_movements');
        Schema::dropIfExists('cash_register_shifts');
    }
};
