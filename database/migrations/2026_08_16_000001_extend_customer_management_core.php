<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code')->nullable()->after('branch_id');
            }

            if (! Schema::hasColumn('customers', 'address')) {
                $table->text('address')->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('customers', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('notes');
            }

            if (! Schema::hasColumn('customers', 'last_visit_at')) {
                $table->timestamp('last_visit_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('customers')
            ->whereNull('customer_code')
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->get(['id', 'tenant_id'])
            ->each(function ($customer) {
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'customer_code' => 'CUS-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });

        Schema::table('customers', function (Blueprint $table) {
            if (! $this->indexExists('customers', 'customers_tenant_id_customer_code_unique')) {
                $table->unique(['tenant_id', 'customer_code']);
            }

            if (! $this->indexExists('customers', 'customers_tenant_id_status_index')) {
                $table->index(['tenant_id', 'status']);
            }

            if (! $this->indexExists('customers', 'customers_tenant_id_branch_id_index')) {
                $table->index(['tenant_id', 'branch_id']);
            }

            if (! $this->indexExists('customers', 'customers_tenant_id_phone_index')) {
                $table->index(['tenant_id', 'phone']);
            }

            if (! $this->indexExists('customers', 'customers_tenant_id_email_index')) {
                $table->index(['tenant_id', 'email']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach ([
                'customers_tenant_id_email_index',
                'customers_tenant_id_phone_index',
                'customers_tenant_id_branch_id_index',
                'customers_tenant_id_status_index',
            ] as $index) {
                if ($this->indexExists('customers', $index)) {
                    $table->dropIndex($index);
                }
            }

            if ($this->indexExists('customers', 'customers_tenant_id_customer_code_unique')) {
                $table->dropUnique('customers_tenant_id_customer_code_unique');
            }

            if (Schema::hasColumn('customers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['last_visit_at', 'marketing_consent', 'address', 'customer_code'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('customers', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing) => $existing['name'] === $index);
    }
};
