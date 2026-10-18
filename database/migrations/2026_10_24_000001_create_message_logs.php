<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_logs')) {
            Schema::create('message_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('channel', 20);
                $table->string('provider', 30)->nullable();
                $table->string('event', 40)->index();
                $table->string('recipient', 30);
                $table->text('body');
                $table->string('status', 20)->default('queued')->index();
                $table->string('provider_message_id')->nullable();
                $table->text('error')->nullable();
                $table->nullableMorphs('related');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
        });

        Schema::dropIfExists('message_logs');
    }
};
