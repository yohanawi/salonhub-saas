<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branch_special_hours')) {
            return;
        }

        Schema::create('branch_special_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('label')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'date'], 'branch_special_hours_unique');
            $table->index(['tenant_id', 'branch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_special_hours');
    }
};
