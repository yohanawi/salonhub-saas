<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'anniversary_date')) {
                $table->date('anniversary_date')->nullable()->after('date_of_birth');
                $table->text('allergies')->nullable()->after('notes');
                $table->string('referral_code', 20)->nullable()->after('customer_code');
                $table->foreignId('referred_by_customer_id')->nullable()->after('referral_code')->constrained('customers')->nullOnDelete();
                $table->timestamp('referral_rewarded_at')->nullable()->after('referred_by_customer_id');

                $table->unique(['tenant_id', 'referral_code']);
            }
        });

        if (! Schema::hasTable('customer_photos')) {
            Schema::create('customer_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('kind', 20)->default('before');
                $table->string('path');
                $table->string('caption')->nullable();
                $table->date('taken_on')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'customer_id']);
            });
        }

        if (! Schema::hasTable('customer_consultations')) {
            Schema::create('customer_consultations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->json('answers');
                $table->boolean('consent_given')->default(false);
                $table->string('consent_name')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'customer_id']);
            });
        }

        if (! Schema::hasTable('customer_campaigns')) {
            Schema::create('customer_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('segment', 40);
                $table->text('message');
                $table->unsignedInteger('recipients_count')->default(0);
                $table->unsignedInteger('queued_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        Permission::query()->firstOrCreate(['name' => 'customer.campaign', 'guard_name' => 'web']);
        Role::query()->whereIn('name', ['Super Admin', 'Salon Owner', 'Salon Admin', 'Branch Manager'])->get()
            ->each(fn (Role $role) => $role->givePermissionTo('customer.campaign'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'customer.campaign')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('customer_campaigns');
        Schema::dropIfExists('customer_consultations');
        Schema::dropIfExists('customer_photos');

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'anniversary_date')) {
                $table->dropUnique(['tenant_id', 'referral_code']);
                $table->dropConstrainedForeignId('referred_by_customer_id');
                $table->dropColumn(['anniversary_date', 'allergies', 'referral_code', 'referral_rewarded_at']);
            }
        });
    }
};
