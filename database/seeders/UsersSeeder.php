<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        foreach (self::ROLE_USERS as $role => $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]
            );

            $user->syncRoles([$role]);

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
