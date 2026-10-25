<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'cancelled_late')) {
                // Cancelled inside the salon's cancellation cut-off (booking.cancellation_cutoff_hours).
                $table->boolean('cancelled_late')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'cancelled_late')) {
                $table->dropColumn('cancelled_late');
            }
        });
    }
};
