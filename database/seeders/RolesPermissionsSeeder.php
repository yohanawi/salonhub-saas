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

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        Permission::query()->delete();

        Role::query()
            ->whereNotIn('name', self::ROLES)
            ->delete();

        foreach (self::ROLES as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
