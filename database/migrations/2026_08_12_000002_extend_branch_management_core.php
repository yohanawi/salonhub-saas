<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('branches', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }

            if (! Schema::hasColumn('branches', 'district')) {
                $table->string('district')->nullable()->after('city');
            }

            if (! Schema::hasColumn('branches', 'country')) {
                $table->string('country', 2)->default('LK')->after('postal_code');
            }

            if (! Schema::hasColumn('branches', 'currency')) {
                $table->string('currency', 3)->default('LKR')->after('country');
            }

            if (! Schema::hasColumn('branches', 'timezone')) {
                $table->string('timezone')->default('Asia/Colombo')->after('currency');
            }

            if (! Schema::hasColumn('branches', 'invoice_prefix')) {
                $table->string('invoice_prefix', 20)->nullable()->after('timezone');
            }

            if (! Schema::hasColumn('branches', 'tax_enabled')) {
                $table->boolean('tax_enabled')->default(false)->after('invoice_prefix');
            }

            if (! Schema::hasColumn('branches', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('tax_enabled');
            }

            if (! Schema::hasColumn('branches', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 4)->nullable()->after('tax_name');
            }

            if (! Schema::hasColumn('branches', 'tax_number')) {
                $table->string('tax_number', 100)->nullable()->after('tax_rate');
            }

            if (! Schema::hasColumn('branches', 'is_main')) {
                $table->boolean('is_main')->default(false)->after('tax_number');
            }

            if (! Schema::hasColumn('branches', 'status')) {
                $table->string('status')->default('active')->after('is_main')->index();
            }

            if (! Schema::hasColumn('branches', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'branches_tenant_status_index');
        });

        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['branch_id', 'user_id']);
                $table->index(['tenant_id', 'user_id']);
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS branches_one_main_per_tenant ON branches (tenant_id) WHERE is_main = true AND deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS branches_one_main_per_tenant');
        }

        Schema::dropIfExists('branch_user');

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'status')) {
                $table->dropIndex('branches_tenant_status_index');
            }

            foreach ([
                'deleted_at',
                'status',
                'is_main',
                'tax_number',
                'tax_rate',
                'tax_name',
                'tax_enabled',
                'invoice_prefix',
                'timezone',
                'currency',
                'country',
                'district',
                'address_line_2',
                'address_line_1',
            ] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
