<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'country')) {
                $table->string('country')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('tenants', 'business_type')) {
                $table->string('business_type')->nullable()->after('currency');
            }

            if (! Schema::hasColumn('tenants', 'logo_path')) {
                $table->string('logo_path', 2048)->nullable()->after('business_type');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'job_title')) {
                $table->string('job_title')->nullable()->after('phone');
            }
        });

        Schema::create('branch_business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'day_of_week'], 'branch_hours_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_business_hours');

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'job_title')) {
                $table->dropColumn('job_title');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['logo_path', 'business_type', 'country'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
