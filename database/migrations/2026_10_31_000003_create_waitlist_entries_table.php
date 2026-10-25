<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('waitlist_entries')) {
            return;
        }

        // Customers waiting for a slot on a given day; filled when an appointment that suits them is cancelled.
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            // Null means any staff member who offers the service.
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('preferred_date');
            $table->time('preferred_from')->nullable();
            $table->time('preferred_to')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('waiting');
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'preferred_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
