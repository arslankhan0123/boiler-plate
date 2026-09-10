<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default platform super-admin account (tenant-less).
     *
     * Idempotent: keyed on the admin email so re-seeding never duplicates
     * the row or trips the unique constraints on email/phone.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'superadmin@boilerplate.test'],
            [
                'name' => 'Super Administrator',
                'phone' => '+10000000000',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => null,
                'is_platform_admin' => true,
            ],
        );
    }
}
