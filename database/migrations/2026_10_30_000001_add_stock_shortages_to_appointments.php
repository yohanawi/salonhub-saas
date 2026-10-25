<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'stock_shortages')) {
                // Consumables a completed service could not deduct for lack of stock, kept until a retry deducts them.
                $table->json('stock_shortages')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'stock_shortages')) {
                $table->dropColumn('stock_shortages');
            }
        });
    }
};
