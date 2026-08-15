<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('status');
            }

            if (! Schema::hasColumn('subscriptions', 'billing_period')) {
                $table->string('billing_period')->nullable()->after('price');
            }

            if (! Schema::hasColumn('subscriptions', 'entitlements')) {
                $table->json('entitlements')->nullable()->after('billing_period');
            }

            if (! Schema::hasColumn('subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['cancelled_at', 'entitlements', 'billing_period', 'price'] as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
