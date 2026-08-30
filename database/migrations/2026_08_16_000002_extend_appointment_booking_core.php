<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'appointment_number')) {
                $table->string('appointment_number')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('appointments', 'booking_source')) {
                $table->string('booking_source')->default('reception')->after('appointment_number');
            }

            foreach ([
                'subtotal',
                'discount_amount',
                'tax_amount',
                'total_amount',
            ] as $column) {
                if (! Schema::hasColumn('appointments', $column)) {
                    $table->decimal($column, 12, 2)->default(0);
                }
            }

            if (! Schema::hasColumn('appointments', 'customer_notes')) {
                $table->text('customer_notes')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'internal_notes')) {
                $table->text('internal_notes')->nullable();
            }

            foreach ([
                'checked_in_at',
                'started_at',
                'completed_at',
                'cancelled_at',
            ] as $column) {
                if (! Schema::hasColumn('appointments', $column)) {
                    $table->timestamp($column)->nullable();
                }
            }

            if (! Schema::hasColumn('appointments', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('appointments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('appointments', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        DB::table('appointments')
            ->where('status', 'booked')
            ->update(['status' => 'confirmed']);

        DB::table('appointments')
            ->whereNull('booking_source')
            ->update(['booking_source' => DB::raw("COALESCE(source, 'reception')")]);

        DB::table('appointments')
            ->whereNull('appointment_number')
            ->orderBy('id')
            ->get(['id', 'starts_at'])
            ->each(function ($appointment) {
                $prefix = 'APT-' . ($appointment->starts_at
                    ? \Carbon\Carbon::parse($appointment->starts_at)
                    : now())->format('Ym');

                DB::table('appointments')
                    ->where('id', $appointment->id)
                    ->update([
                        'appointment_number' => $prefix . '-' . str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });

        Schema::table('appointments', function (Blueprint $table) {
            if (! $this->indexExists('appointments', 'appointments_tenant_appointment_number_unique')) {
                $table->unique(['tenant_id', 'appointment_number'], 'appointments_tenant_appointment_number_unique');
            }

            foreach ([
                'appointments_tenant_branch_status_start_index' => ['tenant_id', 'branch_id', 'status', 'starts_at'],
                'appointments_tenant_customer_status_index' => ['tenant_id', 'customer_id', 'status'],
                'appointments_tenant_source_index' => ['tenant_id', 'booking_source'],
            ] as $index => $columns) {
                if (! $this->indexExists('appointments', $index)) {
                    $table->index($columns, $index);
                }
            }
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            if (! Schema::hasColumn('appointment_services', 'service_name')) {
                $table->string('service_name')->nullable()->after('staff_id');
            }

            if (! Schema::hasColumn('appointment_services', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('service_name');
            }

            if (! Schema::hasColumn('appointment_services', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('unit_price');
            }

            if (! Schema::hasColumn('appointment_services', 'total_price')) {
                $table->decimal('total_price', 12, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('appointment_services', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('duration_minutes');
            }

            if (! Schema::hasColumn('appointment_services', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }

            if (! Schema::hasColumn('appointment_services', 'status')) {
                $table->string('status')->default('pending')->after('ends_at');
            }

            if (! Schema::hasColumn('appointment_services', 'created_at')) {
                $table->timestamps();
            }
        });

        DB::table('appointment_services')
            ->whereNull('unit_price')
            ->update(['unit_price' => DB::raw('price')]);

        DB::table('appointment_services')
            ->whereNull('total_price')
            ->update(['total_price' => DB::raw('price')]);

        DB::table('appointment_services')
            ->whereNull('status')
            ->update(['status' => 'pending']);

        Schema::table('appointment_services', function (Blueprint $table) {
            foreach ([
                'appointment_services_tenant_staff_range_index' => ['tenant_id', 'staff_id', 'starts_at', 'ends_at'],
                'appointment_services_tenant_service_index' => ['tenant_id', 'service_id'],
                'appointment_services_status_index' => ['status'],
            ] as $index => $columns) {
                if (! $this->indexExists('appointment_services', $index)) {
                    $table->index($columns, $index);
                }
            }
        });

        Schema::create('appointment_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'appointment_id'], 'appointment_status_history_tenant_appointment_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_status_history');

        Schema::table('appointment_services', function (Blueprint $table) {
            foreach ([
                'appointment_services_status_index',
                'appointment_services_tenant_service_index',
                'appointment_services_tenant_staff_range_index',
            ] as $index) {
                if ($this->indexExists('appointment_services', $index)) {
                    $table->dropIndex($index);
                }
            }

            foreach (['updated_at', 'created_at', 'status', 'ends_at', 'starts_at', 'total_price', 'discount_amount', 'unit_price', 'service_name'] as $column) {
                if (Schema::hasColumn('appointment_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            foreach ([
                'appointments_tenant_source_index',
                'appointments_tenant_customer_status_index',
                'appointments_tenant_branch_status_start_index',
            ] as $index) {
                if ($this->indexExists('appointments', $index)) {
                    $table->dropIndex($index);
                }
            }

            if ($this->indexExists('appointments', 'appointments_tenant_appointment_number_unique')) {
                $table->dropUnique('appointments_tenant_appointment_number_unique');
            }

            foreach (['updated_by', 'created_by', 'cancelled_by'] as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'cancellation_reason',
                'cancelled_at',
                'completed_at',
                'started_at',
                'checked_in_at',
                'internal_notes',
                'customer_notes',
                'total_amount',
                'tax_amount',
                'discount_amount',
                'subtotal',
                'booking_source',
                'appointment_number',
            ] as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing) => $existing['name'] === $index);
    }
};
