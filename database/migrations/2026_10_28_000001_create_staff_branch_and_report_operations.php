<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'attendance.manage',
        'leave.approve',
        'stock_transfer.manage',
        'reports.financial',
        'reports.staff',
    ];

    public function up(): void
    {
        // ------------------------------------------------------------------
        // Staff: attendance, date-specific roster shifts, leave review, ratings
        // ------------------------------------------------------------------
        if (! Schema::hasTable('staff_attendances')) {
            Schema::create('staff_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('work_date');
                $table->timestamp('clock_in_at')->nullable();
                $table->timestamp('clock_out_at')->nullable();
                $table->unsignedInteger('break_minutes')->default(0);
                $table->unsignedInteger('worked_minutes')->nullable();
                $table->time('scheduled_start')->nullable();
                $table->time('scheduled_end')->nullable();
                $table->unsignedInteger('late_minutes')->default(0);
                $table->string('status')->default('present')->index();
                $table->string('source')->default('self');
                $table->text('notes')->nullable();
                $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['staff_id', 'work_date']);
                $table->index(['tenant_id', 'work_date']);
            });
        }

        if (! Schema::hasTable('staff_shifts')) {
            Schema::create('staff_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('shift_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('is_off')->default(false);
                $table->string('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['staff_id', 'shift_date']);
                $table->index(['tenant_id', 'branch_id', 'shift_date']);
            });
        }

        Schema::table('staff_time_off', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_time_off', 'reviewed_by')) {
                $table->foreignId('requested_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                $table->text('review_notes')->nullable()->after('reviewed_at');
            }
        });

        if (! Schema::hasTable('appointment_feedback')) {
            Schema::create('appointment_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('comment')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique('appointment_id');
                $table->index(['tenant_id', 'staff_id', 'submitted_at']);
            });
        }

        // ------------------------------------------------------------------
        // Branches: stock transfers and branch product prices
        // ------------------------------------------------------------------
        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('transfer_number')->nullable();
                $table->string('status')->default('draft')->index();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'transfer_number']);
            });
        }

        if (! Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('quantity_received')->nullable();
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['stock_transfer_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('branch_products')) {
            Schema::create('branch_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->decimal('selling_price', 12, 2);
                $table->timestamps();

                $table->unique(['branch_id', 'product_id']);
            });
        }

        // ------------------------------------------------------------------
        // Scheduled report emails
        // ------------------------------------------------------------------
        if (! Schema::hasTable('report_schedules')) {
            Schema::create('report_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('report');
                $table->string('frequency')->default('weekly');
                $table->unsignedTinyInteger('day_of_week')->nullable();
                $table->unsignedTinyInteger('day_of_month')->nullable();
                $table->string('send_time', 5)->default('08:00');
                $table->string('format', 10)->default('pdf');
                $table->json('recipients');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamp('next_run_at')->nullable()->index();
                $table->timestamps();
            });
        }

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::query()->whereIn('name', ['Super Admin', 'Salon Owner', 'Salon Admin'])->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSIONS));
        Role::query()->where('name', 'Branch Manager')->get()
            ->each(fn (Role $role) => $role->givePermissionTo(['attendance.manage', 'leave.approve', 'stock_transfer.manage', 'reports.staff']));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('branch_products');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('appointment_feedback');

        Schema::table('staff_time_off', function (Blueprint $table) {
            if (Schema::hasColumn('staff_time_off', 'reviewed_by')) {
                $table->dropConstrainedForeignId('requested_by');
                $table->dropConstrainedForeignId('reviewed_by');
                $table->dropColumn(['reviewed_at', 'review_notes']);
            }
        });

        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('staff_attendances');
    }
};
