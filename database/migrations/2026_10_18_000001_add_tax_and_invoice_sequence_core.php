<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        // Legacy numbers were built from the global sale id, so starting each tenant above its
        // highest id guarantees new sequential numbers never collide with existing ones.
        DB::table('sales')
            ->select('tenant_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('tenant_id')
            ->get()
            ->each(fn ($row) => DB::table('invoice_sequences')->insert([
                'tenant_id' => $row->tenant_id,
                'last_number' => (int) $row->max_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'tax_name')) {
                $table->string('tax_name', 50)->nullable()->after('tax');
            }

            if (! Schema::hasColumn('sales', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('tax_name');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 4)->default(0)->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach (['tax_inclusive', 'tax_name'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('invoice_sequences');
    }
};
