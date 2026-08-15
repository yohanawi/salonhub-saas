<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'employee_code')) {
                $table->string('employee_code')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('staff', 'gender')) {
                $table->string('gender')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('staff', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }

            if (! Schema::hasColumn('staff', 'job_title')) {
                $table->string('job_title')->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('staff', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('hire_date');
            }

            if (! Schema::hasColumn('staff', 'bio')) {
                $table->text('bio')->nullable()->after('profile_photo');
            }

            if (! Schema::hasColumn('staff', 'is_bookable')) {
                $table->boolean('is_bookable')->default(false)->after('bio');
            }

            if (! Schema::hasColumn('staff', 'show_online')) {
                $table->boolean('show_online')->default(false)->after('is_bookable');
            }

            if (! Schema::hasColumn('staff', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('staff')
            ->whereNull('employee_code')
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->get(['id', 'tenant_id'])
            ->groupBy('tenant_id')
            ->each(function ($staffRows) {
                $staffRows->values()->each(function ($staff, int $index) {
                    DB::table('staff')
                        ->where('id', $staff->id)
                        ->update(['employee_code' => 'STF-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)]);
                });
            });

        Schema::table('staff', function (Blueprint $table) {
            if (! $this->indexExists('staff', 'staff_tenant_id_employee_code_unique')) {
                $table->unique(['tenant_id', 'employee_code']);
            }

            if (! $this->indexExists('staff', 'staff_tenant_id_status_index')) {
                $table->index(['tenant_id', 'status']);
            }

            if (! $this->indexExists('staff', 'staff_tenant_id_is_bookable_index')) {
                $table->index(['tenant_id', 'is_bookable']);
            }
        });

        Schema::table('staff_branches', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_branches', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('branch_id');
            }

            if (! Schema::hasColumn('staff_branches', 'status')) {
                $table->string('status')->default('active')->after('is_primary');
            }

            if (! Schema::hasColumn('staff_branches', 'created_at')) {
                $table->timestamps();
            }

            if (! $this->indexExists('staff_branches', 'staff_branches_branch_id_status_index')) {
                $table->index(['branch_id', 'status']);
            }
        });

        Schema::table('staff_services', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_services', 'custom_duration_minutes')) {
                $table->unsignedInteger('custom_duration_minutes')->nullable()->after('service_id');
            }

            if (! Schema::hasColumn('staff_services', 'custom_price')) {
                $table->decimal('custom_price', 12, 2)->nullable()->after('custom_duration_minutes');
            }

            if (! Schema::hasColumn('staff_services', 'status')) {
                $table->string('status')->default('active')->after('custom_price');
            }

            if (! Schema::hasColumn('staff_services', 'created_at')) {
                $table->timestamps();
            }

            if (! $this->indexExists('staff_services', 'staff_services_service_id_status_index')) {
                $table->index(['service_id', 'status']);
            }
        });

        $this->dropStaffScheduleUniqueConstraint();

        Schema::table('staff_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_schedules', 'title')) {
                $table->string('title')->nullable()->after('branch_id');
            }

            if (! $this->indexExists('staff_schedules', 'staff_schedules_staff_branch_day_index')) {
                $table->index(['staff_id', 'branch_id', 'day_of_week'], 'staff_schedules_staff_branch_day_index');
            }
        });

        Schema::create('staff_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'staff_id', 'branch_id', 'day_of_week'], 'staff_breaks_tenant_staff_branch_day_index');
        });

        Schema::create('staff_time_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->string('type')->default('unavailable');
            $table->text('reason')->nullable();
            $table->string('status')->default('approved')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'staff_id', 'start_datetime', 'end_datetime'], 'staff_time_off_tenant_staff_range_index');
        });

        Schema::create('staff_commission_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('commission_type')->default('percentage');
            $table->decimal('commission_value', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'staff_id', 'service_id'], 'staff_commission_settings_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_commission_settings');
        Schema::dropIfExists('staff_time_off');
        Schema::dropIfExists('staff_breaks');

        Schema::table('staff_schedules', function (Blueprint $table) {
            if ($this->indexExists('staff_schedules', 'staff_schedules_staff_branch_day_index')) {
                $table->dropIndex('staff_schedules_staff_branch_day_index');
            }

            if (Schema::hasColumn('staff_schedules', 'title')) {
                $table->dropColumn('title');
            }

            if (! $this->indexExists('staff_schedules', 'staff_schedule_unique')) {
                $table->unique(['tenant_id', 'staff_id', 'branch_id', 'day_of_week'], 'staff_schedule_unique');
            }
        });

        Schema::table('staff_services', function (Blueprint $table) {
            if ($this->indexExists('staff_services', 'staff_services_service_id_status_index')) {
                $table->dropIndex('staff_services_service_id_status_index');
            }

            foreach (['created_at', 'updated_at', 'status', 'custom_price', 'custom_duration_minutes'] as $column) {
                if (Schema::hasColumn('staff_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('staff_branches', function (Blueprint $table) {
            if ($this->indexExists('staff_branches', 'staff_branches_branch_id_status_index')) {
                $table->dropIndex('staff_branches_branch_id_status_index');
            }

            foreach (['created_at', 'updated_at', 'status', 'is_primary'] as $column) {
                if (Schema::hasColumn('staff_branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            foreach (['staff_tenant_id_is_bookable_index', 'staff_tenant_id_status_index'] as $index) {
                if ($this->indexExists('staff', $index)) {
                    $table->dropIndex($index);
                }
            }

            if ($this->indexExists('staff', 'staff_tenant_id_employee_code_unique')) {
                $table->dropUnique('staff_tenant_id_employee_code_unique');
            }

            foreach (['deleted_at', 'show_online', 'is_bookable', 'bio', 'profile_photo', 'job_title', 'date_of_birth', 'gender', 'employee_code'] as $column) {
                if (Schema::hasColumn('staff', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing) => $existing['name'] === $index);
    }

    private function dropStaffScheduleUniqueConstraint(): void
    {
        if (! $this->indexExists('staff_schedules', 'staff_schedule_unique')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE staff_schedules DROP CONSTRAINT IF EXISTS staff_schedule_unique'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE staff_schedules DROP INDEX staff_schedule_unique'),
            default => Schema::table('staff_schedules', fn (Blueprint $table) => $table->dropUnique('staff_schedule_unique')),
        };
    }
};
