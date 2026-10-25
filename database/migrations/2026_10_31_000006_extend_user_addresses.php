<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'is_primary')) {
                $table->string('label', 50)->nullable()->after('user_id');
                // The address shown on the profile and used as the billing address.
                $table->boolean('is_primary')->default(false)->after('type');
            }

            // Many Sri Lankan addresses have no postcode or province on file.
            $table->string('postal_code')->nullable()->change();
            $table->string('state')->nullable()->change();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['user_id']);

            if (Schema::hasColumn('addresses', 'is_primary')) {
                $table->dropColumn(['label', 'is_primary']);
            }
        });
    }
};
