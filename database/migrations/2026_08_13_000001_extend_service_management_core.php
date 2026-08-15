<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('service_categories', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('service_categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }

            if (! Schema::hasColumn('service_categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('service_categories')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'tenant_id', 'name'])
            ->each(function ($category) {
                DB::table('service_categories')
                    ->where('id', $category->id)
                    ->update(['slug' => Str::slug($category->name) ?: "category-{$category->id}"]);
            });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();

            if (! $this->indexExists('service_categories', 'service_categories_tenant_id_slug_unique')) {
                $table->unique(['tenant_id', 'slug']);
            }

            if (! $this->indexExists('service_categories', 'service_categories_tenant_id_is_active_index')) {
                $table->index(['tenant_id', 'is_active']);
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('services', 'default_price')) {
                $table->decimal('default_price', 12, 2)->default(0)->after('description');
            }

            if (! Schema::hasColumn('services', 'default_duration_minutes')) {
                $table->unsignedInteger('default_duration_minutes')->default(0)->after('default_price');
            }

            if (! Schema::hasColumn('services', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }

            if (! Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('services')
            ->whereNull('slug')
            ->orWhere('default_price', 0)
            ->orWhere('default_duration_minutes', 0)
            ->orderBy('id')
            ->get(['id', 'name', 'price', 'duration_minutes'])
            ->each(function ($service) {
                DB::table('services')
                    ->where('id', $service->id)
                    ->update([
                        'slug' => Str::slug($service->name) ?: "service-{$service->id}",
                        'default_price' => $service->price ?? 0,
                        'default_duration_minutes' => $service->duration_minutes ?? 0,
                    ]);
            });

        Schema::table('services', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();

            if (! $this->indexExists('services', 'services_tenant_id_slug_unique')) {
                $table->unique(['tenant_id', 'slug']);
            }

            if (! $this->indexExists('services', 'services_tenant_id_is_active_index')) {
                $table->index(['tenant_id', 'is_active']);
            }

            if (! $this->indexExists('services', 'services_tenant_id_category_id_index')) {
                $table->index(['tenant_id', 'category_id']);
            }
        });

        Schema::table('branch_services', function (Blueprint $table) {
            if (! Schema::hasColumn('branch_services', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('price');
            }

            if (! Schema::hasColumn('branch_services', 'created_at')) {
                $table->timestamps();
            }

            if (! $this->indexExists('branch_services', 'branch_services_branch_id_is_active_index')) {
                $table->index(['branch_id', 'is_active']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('branch_services', function (Blueprint $table) {
            if ($this->indexExists('branch_services', 'branch_services_branch_id_is_active_index')) {
                $table->dropIndex('branch_services_branch_id_is_active_index');
            }

            if (Schema::hasColumn('branch_services', 'created_at')) {
                $table->dropTimestamps();
            }

            if (Schema::hasColumn('branch_services', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            foreach (['services_tenant_id_category_id_index', 'services_tenant_id_is_active_index'] as $index) {
                if ($this->indexExists('services', $index)) {
                    $table->dropIndex($index);
                }
            }

            if ($this->indexExists('services', 'services_tenant_id_slug_unique')) {
                $table->dropUnique('services_tenant_id_slug_unique');
            }

            foreach (['deleted_at', 'sort_order', 'default_duration_minutes', 'default_price', 'slug'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('service_categories', function (Blueprint $table) {
            if ($this->indexExists('service_categories', 'service_categories_tenant_id_is_active_index')) {
                $table->dropIndex('service_categories_tenant_id_is_active_index');
            }

            if ($this->indexExists('service_categories', 'service_categories_tenant_id_slug_unique')) {
                $table->dropUnique('service_categories_tenant_id_slug_unique');
            }

            foreach (['deleted_at', 'sort_order', 'slug'] as $column) {
                if (Schema::hasColumn('service_categories', $column)) {
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
