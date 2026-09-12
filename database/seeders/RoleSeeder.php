<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'SuperAdmin', // Must be first as requested
            'Customer',
            'Seller',

            // POS & Inventory
            'POS Cashier',
            'Store Manager',
            'Inventory Manager',

            // E-commerce
            'E-commerce Manager',
            'Order Fulfillment Agent',

            // System & Support
            'Admin',
            'Editor',
            'Support Agent',
            'Marketing',
            'Finance'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api']);
        }

        // Create a SuperAdmin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@boilerplate.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
            ]
        );

        $superAdmin->assignRole('SuperAdmin');
    }
}
