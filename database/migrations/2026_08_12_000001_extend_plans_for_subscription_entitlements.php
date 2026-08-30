<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'max_users')) {
                $table->unsignedInteger('max_users')->nullable();
            }

            if (! Schema::hasColumn('plans', 'max_customers')) {
                $table->unsignedInteger('max_customers')->nullable();
            }

            if (! Schema::hasColumn('plans', 'trial_days')) {
                $table->unsignedInteger('trial_days')->default(0);
            }

            if (! Schema::hasColumn('plans', 'is_recommended')) {
                $table->boolean('is_recommended')->default(false);
            }

            if (! Schema::hasColumn('plans', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            foreach (['max_users', 'max_customers', 'trial_days', 'is_recommended', 'sort_order'] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
