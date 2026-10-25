<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // Services: images, online listing, add-ons and consumables
        // ------------------------------------------------------------------
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
                $table->text('online_description')->nullable()->after('image_path');
                $table->boolean('show_online')->default(true)->after('online_description');
                $table->boolean('is_addon')->default(false)->after('show_online');
            }
        });

        if (! Schema::hasTable('service_addons')) {
            Schema::create('service_addons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->foreignId('addon_service_id')->constrained('services')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['service_id', 'addon_service_id']);
            });
        }

        if (! Schema::hasTable('service_products')) {
            Schema::create('service_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->timestamps();

                $table->unique(['service_id', 'product_id']);
            });
        }

        // ------------------------------------------------------------------
        // Suppliers & purchase orders
        // ------------------------------------------------------------------
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('name');
                $table->string('tax_number')->nullable()->after('address');
                $table->text('notes')->nullable()->after('tax_number');
                $table->boolean('is_active')->default(true)->after('notes');
                $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'purchase_number')) {
                $table->string('purchase_number')->nullable()->after('supplier_id');
                $table->date('expected_date')->nullable()->after('purchase_date');
                $table->text('notes')->nullable()->after('total');
                $table->timestamp('ordered_at')->nullable()->after('status');
                $table->timestamp('received_at')->nullable()->after('ordered_at');
                $table->timestamp('cancelled_at')->nullable()->after('received_at');

                $table->unique(['tenant_id', 'purchase_number']);
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'quantity_received')) {
                $table->unsignedInteger('quantity_received')->default(0)->after('quantity');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'track_batches')) {
                $table->boolean('track_batches')->default(false)->after('track_inventory');
            }
        });

        if (! Schema::hasTable('product_batches')) {
            Schema::create('product_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('batch_number');
                $table->date('expiry_date')->nullable();
                $table->integer('quantity_received')->default(0);
                $table->integer('quantity_remaining')->default(0);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->timestamp('received_at')->nullable();
                $table->nullableMorphs('source');
                $table->timestamp('expiry_notified_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'branch_id', 'product_id', 'expiry_date'], 'product_batches_stock_index');
            });
        }

        if (! Schema::hasTable('purchase_receipts')) {
            Schema::create('purchase_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
                $table->string('receipt_number')->nullable();
                $table->string('supplier_reference')->nullable();
                $table->timestamp('received_at');
                $table->text('notes')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'receipt_number']);
            });
        }

        if (! Schema::hasTable('purchase_receipt_items')) {
            Schema::create('purchase_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_receipt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->string('batch_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }

        // ------------------------------------------------------------------
        // Low stock alerts & stock takes
        // ------------------------------------------------------------------
        Schema::table('inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('inventories', 'low_stock_notified_at')) {
                // Set once an alert went out; cleared when stock is replenished so the next dip alerts again.
                $table->timestamp('low_stock_notified_at')->nullable()->after('last_adjusted_at');
            }
        });

        if (! Schema::hasTable('stock_takes')) {
            Schema::create('stock_takes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->string('stock_take_number')->nullable();
                $table->string('status')->default('in_progress')->index();
                $table->text('notes')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->foreignId('stock_adjustment_id')->nullable()->constrained('stock_adjustments')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'stock_take_number']);
            });
        }

        if (! Schema::hasTable('stock_take_items')) {
            Schema::create('stock_take_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->integer('system_quantity')->default(0);
                $table->integer('counted_quantity')->nullable();
                $table->integer('variance')->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->unique(['stock_take_id', 'product_id']);
            });
        }

        // ------------------------------------------------------------------
        // Tiered commissions
        // ------------------------------------------------------------------
        Schema::table('commission_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_rules', 'tiers')) {
                $table->json('tiers')->nullable()->after('commission_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            if (Schema::hasColumn('commission_rules', 'tiers')) {
                $table->dropColumn('tiers');
            }
        });

        Schema::dropIfExists('stock_take_items');
        Schema::dropIfExists('stock_takes');

        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'low_stock_notified_at')) {
                $table->dropColumn('low_stock_notified_at');
            }
        });

        Schema::dropIfExists('purchase_receipt_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('product_batches');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'track_batches')) {
                $table->dropColumn('track_batches');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'quantity_received')) {
                $table->dropColumn('quantity_received');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'purchase_number')) {
                $table->dropUnique(['tenant_id', 'purchase_number']);
                $table->dropColumn(['purchase_number', 'expected_date', 'notes', 'ordered_at', 'received_at', 'cancelled_at']);
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'contact_person')) {
                $table->dropConstrainedForeignId('created_by');
                $table->dropColumn(['contact_person', 'tax_number', 'notes', 'is_active']);
            }
        });

        Schema::dropIfExists('service_products');
        Schema::dropIfExists('service_addons');

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'image_path')) {
                $table->dropColumn(['image_path', 'online_description', 'show_online', 'is_addon']);
            }
        });
    }
};
