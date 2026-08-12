<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    public const ROLES = [
        'Super Admin',
        'Salon Owner',
        'Salon Admin',
        'Branch Manager',
        'Receptionist',
        'Cashier',
        'Beautician',
        'Customer',
    ];

    public const PERMISSIONS = [
        'branches.view',
        'branches.create',
        'branches.update',
        'branches.change_status',
        'branches.manage_hours',
        'branches.manage_tax',
        'branches.manage_staff',
        'branches.set_main',
        'reports.view_branch',
        'reports.view_all_branches',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        Permission::query()
            ->whereNotIn('name', self::PERMISSIONS)
            ->delete();

        Role::query()
            ->whereNotIn('name', self::ROLES)
            ->delete();

        foreach (self::ROLES as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::findByName('Super Admin')->givePermissionTo(self::PERMISSIONS);
        Role::findByName('Salon Owner')->givePermissionTo(self::PERMISSIONS);
        Role::findByName('Salon Admin')->givePermissionTo(self::PERMISSIONS);
        Role::findByName('Branch Manager')->givePermissionTo([
            'branches.view',
            'branches.update',
            'branches.manage_hours',
            'reports.view_branch',
        ]);
        Role::findByName('Receptionist')->givePermissionTo([
            'branches.view',
            'reports.view_branch',
        ]);
        Role::findByName('Cashier')->givePermissionTo([
            'branches.view',
            'reports.view_branch',
        ]);
        Role::findByName('Beautician')->givePermissionTo([
            'branches.view',
        ]);
    }
}
