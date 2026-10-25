<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'supplier.view',
        'supplier.manage',
        'purchase.view',
        'purchase.manage',
        'purchase.receive',
        'stock_take.manage',
    ];

    private const ROLES = ['Super Admin', 'Salon Owner', 'Salon Admin', 'Branch Manager'];

    public function up(): void
    {
        // Existing installs get the new permissions without re-running the full seeder.
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
    }
};
