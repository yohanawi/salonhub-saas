<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'pos_pin')) {
                // Bcrypt hash of the cashier PIN, never the PIN itself.
                $table->string('pos_pin')->nullable()->after('commission_value');
                $table->timestamp('pos_pin_updated_at')->nullable()->after('pos_pin');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'cashier_staff_id')) {
                $table->foreignId('cashier_staff_id')->nullable()->after('created_by')->constrained('staff')->nullOnDelete();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cashier_staff_id')) {
                $table->foreignId('cashier_staff_id')->nullable()->after('received_by')->constrained('staff')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cashier_staff_id')) {
                $table->dropConstrainedForeignId('cashier_staff_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'cashier_staff_id')) {
                $table->dropConstrainedForeignId('cashier_staff_id');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'pos_pin')) {
                $table->dropColumn(['pos_pin', 'pos_pin_updated_at']);
            }
        });
    }
};
