<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_brands')) {
            Schema::create('product_brands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'name']);
            });
        }

        if (! Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('symbol', 30);
                $table->string('type')->default('piece')->index();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'name']);
                $table->unique(['tenant_id', 'symbol']);
            });
        }

        Schema::table('product_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('product_categories', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('product_categories', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('product_categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('product_brands')->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('brand_id')->constrained('units')->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('barcode');
            }

            if (! Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type')->default('retail')->after('description')->index();
            }

            if (! Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 4)->default(0)->after('selling_price');
            }

            if (! Schema::hasColumn('products', 'track_inventory')) {
                $table->boolean('track_inventory')->default(true)->after('tax_rate');
            }

            if (! Schema::hasColumn('products', 'minimum_stock_level')) {
                $table->unsignedInteger('minimum_stock_level')->default(0)->after('reorder_level');
            }

            if (! Schema::hasColumn('products', 'allow_negative_stock')) {
                $table->boolean('allow_negative_stock')->default(false)->after('minimum_stock_level');
            }

            if (! Schema::hasColumn('products', 'is_sellable')) {
                $table->boolean('is_sellable')->default(true)->after('allow_negative_stock');
            }

            if (! Schema::hasColumn('products', 'is_consumable')) {
                $table->boolean('is_consumable')->default(false)->after('is_sellable');
            }

            if (! Schema::hasColumn('products', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['tenant_id', 'product_type'], 'products_tenant_type_index');
            $table->index(['tenant_id', 'is_active'], 'products_tenant_active_index');
            $table->index(['tenant_id', 'is_sellable'], 'products_tenant_sellable_index');
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('inventories', 'quantity_on_hand')) {
                $table->integer('quantity_on_hand')->default(0)->after('product_id');
            }

            if (! Schema::hasColumn('inventories', 'quantity_reserved')) {
                $table->integer('quantity_reserved')->default(0)->after('quantity_on_hand');
            }

            if (! Schema::hasColumn('inventories', 'average_cost')) {
                $table->decimal('average_cost', 12, 2)->default(0)->after('quantity_reserved');
            }

            if (! Schema::hasColumn('inventories', 'last_received_at')) {
                $table->timestamp('last_received_at')->nullable()->after('average_cost');
            }

            if (! Schema::hasColumn('inventories', 'last_sold_at')) {
                $table->timestamp('last_sold_at')->nullable()->after('last_received_at');
            }

            if (! Schema::hasColumn('inventories', 'last_adjusted_at')) {
                $table->timestamp('last_adjusted_at')->nullable()->after('last_sold_at');
            }
        });

        DB::table('inventories')
            ->where('quantity_on_hand', 0)
            ->where('quantity', '!=', 0)
            ->update(['quantity_on_hand' => DB::raw('quantity')]);

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'quantity_before')) {
                $table->integer('quantity_before')->default(0)->after('quantity');
            }

            if (! Schema::hasColumn('stock_movements', 'quantity_after')) {
                $table->integer('quantity_after')->default(0)->after('quantity_before');
            }

            if (! Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('quantity_after');
            }

            if (! Schema::hasColumn('stock_movements', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->default(0)->after('unit_cost');
            }

            if (! Schema::hasColumn('stock_movements', 'reason')) {
                $table->string('reason')->nullable()->after('total_cost');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['tenant_id', 'branch_id', 'product_id'], 'stock_movements_stock_index');
            $table->index(['tenant_id', 'type', 'created_at'], 'stock_movements_type_date_index');
        });

        if (! Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->string('adjustment_number');
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->string('status')->default('approved')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'adjustment_number']);
                $table->index(['tenant_id', 'branch_id', 'status']);
            });
        }

        if (! Schema::hasTable('stock_adjustment_items')) {
            Schema::create('stock_adjustment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->integer('system_quantity')->default(0);
                $table->integer('actual_quantity')->default(0);
                $table->integer('difference')->default(0);
                $table->string('reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
