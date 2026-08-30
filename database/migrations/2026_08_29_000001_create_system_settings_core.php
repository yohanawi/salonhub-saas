<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('scope')->default('platform');
            $table->string('group')->index();
            $table->string('key')->index();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['scope', 'key'], 'settings_scope_key_unique');
            $table->index(['tenant_id', 'branch_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
