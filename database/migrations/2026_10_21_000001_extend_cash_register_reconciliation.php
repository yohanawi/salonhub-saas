<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_register_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_register_shifts', 'counted_denominations')) {
                $table->json('counted_denominations')->nullable()->after('counted_cash');
            }
        });

        Schema::table('expense_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_payments', 'cash_register_shift_id')) {
                $table->foreignId('cash_register_shift_id')->nullable()->after('payment_method_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_payments', function (Blueprint $table) {
            if (Schema::hasColumn('expense_payments', 'cash_register_shift_id')) {
                $table->dropConstrainedForeignId('cash_register_shift_id');
            }
        });

        Schema::table('cash_register_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('cash_register_shifts', 'counted_denominations')) {
                $table->dropColumn('counted_denominations');
            }
        });
    }
};
