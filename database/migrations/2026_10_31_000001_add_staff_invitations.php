<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['staff.manage_access'];

    private const ROLES = ['Super Admin', 'Salon Owner', 'Salon Admin'];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'invited_at')) {
                // Set when a salon invites a staff member to log in; the invite link is only valid for this timestamp.
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('invitation_accepted_at')->nullable();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        // Existing installs get the new permission without re-running the full seeder.
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

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'invited_at')) {
                $table->dropConstrainedForeignId('invited_by');
                $table->dropColumn(['invited_at', 'invitation_accepted_at']);
            }
        });
    }
};
