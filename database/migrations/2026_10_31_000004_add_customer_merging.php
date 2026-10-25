<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['customer.merge'];

    private const ROLES = ['Super Admin', 'Salon Owner', 'Salon Admin'];

    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'merged_into_customer_id')) {
                // Set on the duplicate record (which is then archived) so a merge can be traced afterwards.
                $table->foreignId('merged_into_customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->timestamp('merged_at')->nullable();
            }
        });

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::query()
            ->whereIn('name', self::ROLES)
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSIONS));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'merged_into_customer_id')) {
                $table->dropConstrainedForeignId('merged_into_customer_id');
                $table->dropColumn('merged_at');
            }
        });
    }
};
