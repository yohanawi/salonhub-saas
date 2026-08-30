<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_logs', 'event')) {
                $table->string('event')->nullable()->after('action')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module')->nullable()->after('event')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'description')) {
                $table->text('description')->nullable()->after('module');
            }

            if (! Schema::hasColumn('audit_logs', 'device')) {
                $table->string('device')->nullable()->after('user_agent');
            }

            if (! Schema::hasColumn('audit_logs', 'url')) {
                $table->text('url')->nullable()->after('device');
            }

            if (! Schema::hasColumn('audit_logs', 'request_method')) {
                $table->string('request_method', 12)->nullable()->after('url');
            }

            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('request_method');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! $this->indexExists('audit_logs', 'audit_logs_tenant_branch_created_index')) {
                $table->index(['tenant_id', 'branch_id', 'created_at'], 'audit_logs_tenant_branch_created_index');
            }

            if (! $this->indexExists('audit_logs', 'audit_logs_tenant_module_created_index')) {
                $table->index(['tenant_id', 'module', 'created_at'], 'audit_logs_tenant_module_created_index');
            }

            if (! $this->indexExists('audit_logs', 'audit_logs_tenant_action_created_index')) {
                $table->index(['tenant_id', 'action', 'created_at'], 'audit_logs_tenant_action_created_index');
            }

            if (! $this->indexExists('audit_logs', 'audit_logs_tenant_user_created_index')) {
                $table->index(['tenant_id', 'user_id', 'created_at'], 'audit_logs_tenant_user_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach ([
                'audit_logs_tenant_user_created_index',
                'audit_logs_tenant_action_created_index',
                'audit_logs_tenant_module_created_index',
                'audit_logs_tenant_branch_created_index',
            ] as $index) {
                if ($this->indexExists('audit_logs', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }

            foreach (['metadata', 'request_method', 'url', 'device', 'description', 'module', 'event'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
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
