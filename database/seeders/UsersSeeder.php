<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    private const ROLE_USERS = [
        'Super Admin' => [
            'name' => 'Super Admin',
            'email' => 'superadmin@saloonhub.test',
            'phone' => '+94770000001',
        ],
        'Salon Owner' => [
            'name' => 'Salon Owner',
            'email' => 'owner@saloonhub.test',
            'phone' => '+94770000002',
        ],
        'Salon Admin' => [
            'name' => 'Salon Admin',
            'email' => 'salonadmin@saloonhub.test',
            'phone' => '+94770000003',
        ],
        'Branch Manager' => [
            'name' => 'Branch Manager',
            'email' => 'branchmanager@saloonhub.test',
            'phone' => '+94770000004',
        ],
        'Receptionist' => [
            'name' => 'Receptionist',
            'email' => 'receptionist@saloonhub.test',
            'phone' => '+94770000005',
        ],
        'Cashier' => [
            'name' => 'Cashier',
            'email' => 'cashier@saloonhub.test',
            'phone' => '+94770000006',
        ],
        'Beautician' => [
            'name' => 'Beautician',
            'email' => 'beautician@saloonhub.test',
            'phone' => '+94770000007',
        ],
        'Customer' => [
            'name' => 'Customer',
            'email' => 'customer@saloonhub.test',
            'phone' => '+94770000008',
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo-salon'],
            [
                'name' => 'Demo Salon',
                'email' => 'demo@saloonhub.test',
                'phone' => '+94770000999',
                'status' => 'active',
                'timezone' => 'Asia/Colombo',
                'currency' => 'LKR',
                'country' => 'Sri Lanka',
                'business_type' => 'Beauty Salon',
            ]
        );

        $branch = Branch::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'code' => 'MAIN',
            ],
            [
                'name' => 'Main Branch',
                'email' => 'main@saloonhub.test',
                'phone' => '+94770000888',
                'address' => 'No. 10, Main Street',
                'address_line_1' => 'No. 10, Main Street',
                'city' => 'Colombo',
                'country' => 'LK',
                'currency' => 'LKR',
                'timezone' => 'Asia/Colombo',
                'status' => Branch::STATUS_ACTIVE,
                'is_active' => true,
                'is_main' => true,
            ]
        );

        foreach (self::ROLE_USERS as $role => $data) {
            $isTenantUser = $role !== 'Super Admin';

            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'tenant_id' => $isTenantUser ? $tenant->id : null,
                    'branch_id' => $isTenantUser ? $branch->id : null,
                    'first_name' => Str::before($data['name'], ' '),
                    'last_name' => Str::after($data['name'], ' ') !== $data['name'] ? Str::after($data['name'], ' ') : null,
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]
            );

            $user->syncRoles([$role]);

            if ($isTenantUser && in_array($role, ['Branch Manager', 'Receptionist', 'Cashier', 'Beautician'], true)) {
                $branch->users()->syncWithoutDetaching([
                    $user->id => ['tenant_id' => $tenant->id],
                ]);
            }

            Address::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 1,
                ],
                [
                    'address_line_1' => 'No. ' . str_pad((string) $user->id, 2, '0', STR_PAD_LEFT) . ', Main Street',
                    'address_line_2' => null,
                    'city' => 'Colombo',
                    'postal_code' => '00100',
                    'state' => 'Western Province',
                    'country' => 'Sri Lanka',
                ]
            );
        }
    }
}
