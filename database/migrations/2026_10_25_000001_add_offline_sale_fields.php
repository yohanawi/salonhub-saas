<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'client_uuid')) {
                // Set by the POS page per sale, so a retried or re-synced submit never creates a second invoice.
                $table->uuid('client_uuid')->nullable();
                $table->string('offline_reference', 40)->nullable();
                $table->timestamp('offline_created_at')->nullable();

                $table->unique(['tenant_id', 'client_uuid']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'client_uuid')) {
                $table->dropUnique(['tenant_id', 'client_uuid']);
                $table->dropColumn(['client_uuid', 'offline_reference', 'offline_created_at']);
            }
        });
    }
};
