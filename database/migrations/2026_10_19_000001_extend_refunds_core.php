<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('payment_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('refunds', 'transaction_reference')) {
                $table->string('transaction_reference')->nullable()->after('amount');
            }

            if (! Schema::hasColumn('refunds', 'requested_by')) {
                $table->foreignId('requested_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('refunds', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('processed_at');
            }
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('restock')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'sale_item_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'refunded_amount')) {
                $table->decimal('refunded_amount', 12, 2)->default(0)->after('balance_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'refunded_amount')) {
                $table->dropColumn('refunded_amount');
            }
        });

        Schema::dropIfExists('refund_items');

        Schema::table('refunds', function (Blueprint $table) {
            foreach (['requested_by', 'payment_method_id'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['rejection_reason', 'transaction_reference'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
