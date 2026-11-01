<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * php artisan db:seed --class=MasterAdminSeeder
 *
 * Central bootstrap only: the Platform Master Admin (master@admin.com / password)
 * with platform + core permissions. Business modules live inside tenants.
 */
class MasterAdminSeeder extends Seeder
{
    private const GUARD = 'api';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ModuleRegistry::sync();

        $names = [];
        foreach (ModuleRegistry::byScope('platform', 'core') as $code => $m) {
            foreach (array_keys($m['permissions']) as $name) {
                $names[] = Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
            }
        }

        $role = Role::query()->firstOrCreate(['name' => config('modules.master_role'), 'guard_name' => self::GUARD]);
        $role->syncPermissions($names);

        $user = User::query()->updateOrCreate(
            ['email' => 'master@admin.com'],
            ['name' => 'Master Admin', 'password' => Hash::make('password'), 'email_verified_at' => now(), 'tenant_id' => null],
        );
        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command?->info('Master Admin ready: master@admin.com / password');
    }
}
